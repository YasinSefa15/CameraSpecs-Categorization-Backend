<?php

namespace App\Http\Services;

use App\Models\Brand;
use App\Models\Camera;
use App\Models\Image;
use App\Models\SensorInfo;
use App\Models\Specification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class CameraCrawlService
{
    public array $brandNameList;

    public string $domainUrl = 'http://127.0.0.1:8000';

    private array $cameraImages = [];

    public array $cameras = [];

    private array $sensorAndSpecFieldToAnchor = [];

    private array $camerasInDBMapped = [];

    public function initState(): void
    {
        //getting brands in the db
        $brands = Brand::query()->select('name')->get()->pluck('name')->toArray();
        $brandNames = array_map('strtolower', $brands);

        $this->brandNameList = $brandNames;
    }

    public function brandCameras(): void
    {
        $baseUrl = 'https://www.digicamdb.com/cameras/';

        $brands = Brand::query()->select('name', 'id')->whereIn('name', $this->brandNameList)->get();

        foreach ($this->brandNameList as $brandName) {
            $camerasPageUrl = $baseUrl.$brandName.'/1/';

            $responseContent = Cache::remember($camerasPageUrl, 86400 * 24, function () use ($camerasPageUrl) {
                return Http::get($camerasPageUrl)->body();
            });

            $wantedBrand = $brands->first(function ($brand) use ($brandName) {
                return Str::lower($brand->name) === $brandName;
            });

            $this->processCameraPage($responseContent, $wantedBrand, $baseUrl, $brandName);
        }

        $uniqueSpecList = [];
        foreach ($this->sensorAndSpecFieldToAnchor as $index => $item) {
            $key = $item[1]['model'].'_'.$item[1]['brand_id'];
            if (! array_key_exists($key, $uniqueSpecList)) {
                $uniqueSpecList[$key] = $item;
            }
        }
        $uniqueSpecList = array_values($uniqueSpecList);

        Cache::put('sensorFieldToAnchor', $uniqueSpecList, 86400 * 24);
        unset($this->sensorAndSpecFieldToAnchor, $uniqueSpecList);
        gc_collect_cycles();
    }

    private function processCameraPage($responseContent, $wantedBrand, $baseUrl, $brandName, $currentPage = 1): void
    {
        $crawler = new Crawler($responseContent);
        $this->crawlCamerasAndImages($crawler, $wantedBrand);

        $pageCountElement = $crawler->filter('body > #main > div > .relative > .browse_page');
        $totalPage = (int) Str::after($pageCountElement->text(), 'of ');

        if ($currentPage < $totalPage) {
            $cameraUrl = $baseUrl.$brandName.'/'.($currentPage + 1).'/';
            $this->processCameraPage($this->getCachedResponse($cameraUrl), $wantedBrand, $baseUrl, $brandName, $currentPage + 1);
        }
    }

    private function getCachedResponse($cameraUrl): mixed
    {
        return Cache::remember($cameraUrl, 86400 * 24, function () use ($cameraUrl) {
            return Http::get($cameraUrl)->body();
        });
    }

    private function addCameraImagesToCache($imagesUrls): void
    {
        foreach ($imagesUrls as $url) {
            Cache::remember('https://www.digicamdb.com/'.$url, 86400 * 24, function () use ($url) {
                return Http::get('https://www.digicamdb.com/'.$url)->body();
            });
        }
    }

    //crawls the data in the requested dom
    private function crawlCamerasAndImages($crawler, $wantedBrand): void
    {
        $craw = $crawler->filter('body > #main > div > .relative > .newest_div');

        $cameraInfo = $craw->filter('.newest_2')->extract(['href', '_text']);
        $cameraSpecsUrls = $craw->filter('.newest_1 > a')->extract(['href']);
        $cameraViewURLs = $craw->filter('.newest_1 > a > img')->extract(['src']);

        //todo uncomment to add to cache image responses
        //$this->addCameraImagesToCache($cameraViewURLs);

        foreach ($cameraInfo as $index => $element) {

            $elementString = $element[1];
            $cameraSpecsUrl = $cameraSpecsUrls[$index];

            $lines = array_map('trim', explode("\n", $elementString));
            [$cameraTitle, $cameraYear, $megapixels, $sensor] = $this->parseCameraInfo($lines[2], $lines[4]);

            $cameraFilePath = 'https://www.digicamdb.com/'.$cameraViewURLs[$index];

            $imagePath = public_path($cameraViewURLs[$index]);
            if (! file_exists($cameraFilePath)) {
                //todo uncomment to download images
                //file_put_contents($imagePath, Cache::get($cameraViewURLs[$index]));
            }

            $this->cameras[] = [
                'brand_id' => $wantedBrand->id,
                'model' => $cameraTitle,
                'year' => $cameraYear,
                'megapixels' => $megapixels,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $this->cameraImages[] = [
                'identifiers' => [
                    'brand_id' => $wantedBrand->id,
                    'model' => $cameraTitle,
                ],
                'file_path' => $this->domainUrl.'/'.$cameraViewURLs[$index],
            ];

            $this->sensorAndSpecFieldToAnchor[] = [
                'https://www.digicamdb.com/'.$cameraSpecsUrl,
                [
                    'brand_id' => $wantedBrand->id,
                    'model' => $cameraTitle,
                ],
                $sensor,
            ];
        }
    }

    private function parseCameraInfo($titleLine, $sensorLine): array
    {
        $cameraTitle = trim(Str::beforeLast($titleLine, ' ('));
        $cameraYear = (int) Str::beforeLast(Str::beforeLast(Str::afterLast($titleLine, '('), ')'), ') ');
        $megapixels = Str::before($sensorLine, ' megapixels');
        $sensor = Str::between($sensorLine, 'Sensor: ', ',');

        return [$cameraTitle, $cameraYear, $megapixels, $sensor];
    }

    public function insertCameraImagesToDB(): void
    {
        $images = [];
        $uniques = [];
        foreach ($this->cameraImages as $cameraImage) {
            $key = $cameraImage['identifiers']['model'].'_'.$cameraImage['identifiers']['brand_id'];
            if (! array_key_exists($key, $uniques)) {
                $uniques[$key] = $cameraImage;
            }
        }

        foreach ($uniques as $cameraImage) {
            $camera = $this->camerasInDBMapped[Str::lower($cameraImage['identifiers']['model'].'_'.$cameraImage['identifiers']['brand_id'])];
            $images[] = [
                'taggable_id' => $camera,
                'taggable_type' => "App\Models\Camera",
                'file_path' => $cameraImage['file_path'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        $this->cameraImages = [];

        foreach (array_chunk($images, 50) as $batch) {
            Image::query()->insert($batch);
        }

    }

    public function insertCamerasToDB(): void
    {
        $uniques = [];

        foreach ($this->cameras as $camera) {
            $key = $camera['model'].'_'.$camera['brand_id'];
            if (! array_key_exists($key, $uniques)) {
                $uniques[$key] = $camera;
            }
        }

        foreach (array_chunk($uniques, 100) as $batch) {
            Camera::query()->insert($batch);
        }

        $this->cameras = [];
        $this->removeCameraDuplicates();

        $cameras = Camera::query()->select('id', 'brand_id', 'model')->get();

        foreach ($cameras as $camera) {
            $this->camerasInDBMapped[Str::lower($camera->model.'_'.$camera->brand_id)] = $camera->id;
        }

    }

    private function removeCameraDuplicates(): void
    {
        $subquery = Camera::select('id', 'model', 'brand_id', DB::raw('MAX(id) as max_id'))
            ->whereNull('deleted_at')
            ->groupBy('id');

        Camera::leftJoinSub($subquery, 'max_cameras', function ($join) {
            $join->on('cameras.brand_id', '=', 'max_cameras.brand_id')
                ->on('cameras.model', '=', 'max_cameras.model');
        })
            ->where(function ($query) {
                $query
                    ->whereNull('cameras.deleted_at')
                    ->where('cameras.id', '<', DB::raw('max_cameras.max_id'));
            })
            ->delete();
    }

    public function removeOldRecords(): void
    {
        $deletedCamerasID = Camera::onlyTrashed()->select('id')->get()->pluck('id');
        Image::query()->whereIn('taggable_id', $deletedCamerasID)->where('taggable_type', 'App\Models\Camera')->delete();
        SensorInfo::query()->delete();
        Specification::query()->delete();
    }
}
