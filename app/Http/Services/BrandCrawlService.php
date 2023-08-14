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
use Symfony\Component\HttpClient\HttpClient;

class BrandCrawlService
{
    public object $brandElements;

    public string $domainUrl = 'http://127.0.0.1:8000';

    public array $imagesSrc;

    public array $brandImagesToInsert = [];

    private array $brandsToInsert = [];

    public function getBrandsAndImages(): void
    {
        $pageUrl = 'https://www.digicamdb.com/cameras/';

        $httpClient = HttpClient::create();

        if (Cache::has($pageUrl)) {
            $content = Cache::get($pageUrl);
        } else {
            $response = $httpClient->request('GET', $pageUrl);
            $content = $response->getContent();
            Cache::put($pageUrl, $content, 86400 * 24);
        }

        $crawler = new Crawler($content);
        $this->brandElements = $crawler->filter('body > #main > .brd_table > .brd_inner > .font_tiny');
        $images = $crawler->filter('body > #main > .brd_table > .brd_inner > .brd_inner_div > a > img');
        $this->imagesSrc = $images->extract(['src']);

        $this->setBrandsAndImagesFields();
    }

    private function setBrandsAndImagesFields(): void
    {
        foreach ($this->brandElements as $index => $domElement) {
            $elementValue = $domElement->nodeValue;
            $lines = array_filter(array_map('trim', explode("\n", $elementValue)));

            $brandName = Str::lower(Str::before($lines[3], ' '));
            $headquarters = Str::after($lines[1], ': ');
            $isMajor = $index < 11;

            $brandFilePath = 'https://www.digicamdb.com/'.$this->imagesSrc[$index];

            $imageResponse = null;
            if (Cache::has($brandFilePath)) {
                $imageResponse = Cache::get($brandFilePath);
            } else {
                $imageResponse = Http::get($brandFilePath)->body();
                Cache::set($brandFilePath, $imageResponse, 86400 * 24);
            }

            $imagePath = public_path($this->imagesSrc[$index]);

            if (! file_exists($imagePath)) {
                //todo uncomment to download images
                //file_put_contents($imagePath, $imageResponse);
            }

            $this->brandsToInsert[] = [
                'name' => $brandName,
                'headquarters' => $headquarters,
                'is_major' => $isMajor,
            ];

            $this->brandImagesToInsert[] = [
                'identifiers' => [
                    'name' => $brandName,
                    'headquarters' => $headquarters,
                ],
                'taggable_type' => "App\Models\Brand",
                'file_path' => $this->domainUrl.'/'.$this->imagesSrc[$index],
                'created_at' => now(),
                'updated_at' => now(),
            ];
            usleep(50000); // 50 ms bekleme süresi her istekte
        }

    }

    public function insertBrandsAndRemoveDuplicates(): void
    {
        foreach (array_chunk($this->brandsToInsert, 200) as $batch) {
            Brand::query()->insert($batch);
        }

        $subquery = Brand::select('id', 'name', DB::raw('MAX(id) as max_id'))
            ->groupBy('id');

        Brand::leftJoinSub($subquery, 'max_brands', function ($join) {
            $join->on('brands.name', '=', 'max_brands.name');
        })
            ->where(function ($query) {
                $query->where('brands.id', '<', DB::raw('max_brands.max_id'));
            })
            ->delete();

        //deleting cameras, sensors, specifications related to brand
        $trashedBrandIDs = Brand::onlyTrashed()->select('id')->get();
        Camera::query()->whereIn('brand_id', $trashedBrandIDs)->delete();

        $trashedCamerasId = Camera::onlyTrashed()->select('id')->get();

        SensorInfo::query()->delete();
        Image::query()->whereIn('taggable_id', $trashedCamerasId)->delete();
        Specification::query()->delete();
    }

    public function insertImagesAndRemoveDuplicates(): void
    {
        $brandsList = Brand::query()->select('id', 'name', 'headquarters')->get();
        $brands = [];

        foreach ($brandsList as $brand) {
            $brands[Str::lower(Str::lower($brand->name.'_'.$brand->headquarters))] = $brand->id;
        }

        $images = [];
        foreach ($this->brandImagesToInsert as $item) {
            $item['taggable_id'] = $brands[Str::lower($item['identifiers']['name'].'_'.$item['identifiers']['headquarters'])];
            unset($item['identifiers']);
            $images[] = $item;
        }

        foreach (array_chunk($images, 200) as $batch) {
            Image::query()->insert($batch);
        }

        //earlier records deleted
        $trashedBandIDs = Brand::onlyTrashed()->select('id')->get();
        Image::query()
            ->whereIn('taggable_id', $trashedBandIDs)
            ->delete();
    }
}
