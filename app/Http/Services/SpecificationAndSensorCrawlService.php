<?php

namespace App\Http\Services;

use App\Models\Camera;
use App\Models\SensorInfo;
use App\Models\Specification;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class SpecificationAndSensorCrawlService
{
    private array $cameraSpecifications = [];

    private array $sensorInfo = [];

    private array $camerasInDBMapped = [];

    public function iterate(): array
    {
        $specsList = Cache::get('sensorFieldToAnchor');
        if (! $specsList) {
            dd('sensorFieldToAnchor bulunamadı');
        }

        $cameras = Camera::query()->select('id', 'brand_id', 'model')->get();

        foreach ($cameras as $camera) {
            $this->camerasInDBMapped[Str::lower($camera->model.'_'.$camera->brand_id)] = $camera->id;
        }

        $urls = [];
        $i = 0;
        $urlIndexesOfPendingResponses = [];
        $responseUrls = [];
        $responseUrlsCounter = 0;
        foreach (array_chunk($specsList, 100) as $index => $specs) {
            $promises = [];
            foreach ($specs as $spec) {
                if (Cache::has($spec[0])) { //if requested url response body in the cache
                    $urls[$i++] = $spec[0];
                } else {
                    $urlIndexesOfPendingResponses[] = $i;
                    $urls[$i++] = $spec[0];

                    $responseUrls[$responseUrlsCounter++] = $spec[0];
                    $promises[] = Http::async()->get($spec[0]);
                }
            }

            foreach ($promises as $innerIndex => $promise) {
                $response = $promise->wait();
                if ($response instanceof ConnectException) {
                    var_dump('Request failed on url: ', $urls[$urlIndexesOfPendingResponses[$innerIndex]]);
                    unset($urls[$urlIndexesOfPendingResponses[$innerIndex]]);
                    $urls = array_values($urls);

                    continue;
                }

                $body = $response->body();

                Cache::put($responseUrls[$innerIndex], $body, 86400 * 24);
            }

            $responseUrlsCounter = 0;

            usleep(50000); // 50 ms bekleme süresi
        }

        return [$urls, $specsList];
    }

    public function cameraSpecificationsAndSensor($urls, $specList): void
    {
        foreach ($urls as $index => $url) {

            $content = Cache::get($url);
            $crawler = new Crawler($content);
            $sensorDivs = $crawler->filter('body > #main > .s_info_div > .s_info_box');
            $specificationsDOMtd = $crawler->filterXPath('//*[@id="main"]/div[12]/table')->filter('tr');

            if ($specificationsDOMtd->count() == 0) {
                $specificationsDOMtd = $crawler->filterXPath('//*[@id="main"]/div[13]/table')->filter('tr');
            }

            foreach ($specificationsDOMtd as $specification) {
                $valueLines = $specification->nodeValue;
                $lines = array_filter(array_map('trim', explode("\n", $valueLines)));
                //$lines[2] -> title, $lines[5] -> value, 5th index can not be set or 6th, some titles do not have any value
                $title = Str::before($lines[2], ':');
                $value = $lines[5] ?? $lines[6] ?? null;

                $this->cameraSpecifications[] = [
                    'title' => $title,
                    'value' => $value,
                    'identifiers' => $specList[$index][1],
                ];
                unset($valueLines, $lines, $title, $value);
            }

            unset($specificationsDOMtd, $crawler);

            if (count($this->cameraSpecifications) > 200) {
                $this->insertSpecifications();
                $this->cameraSpecifications = [];
            }

            $info = [];
            foreach ($sensorDivs as $sensorDiv) {
                $valueLines = $sensorDiv->nodeValue;
                $lines = array_filter(array_map('trim', explode("\n", $valueLines)));
                $info[Str::lower(Str::replace(' ', '_', $lines[2]))] = $lines[5];
            }

            $this->sensorInfo[] = array_merge($info, ['sensor' => $specList[$index][2], 'identifiers' => $specList[$index][1]]);

            if (count($this->sensorInfo) >= 100) {
                $this->insertSensorInfos();
                $this->sensorInfo = [];
            }
        }
        //if left any in the array
        $this->insertSpecifications();
        $this->insertSensorInfos();
    }

    public function insertSpecifications(): void
    {
        $specificationsData = [];

        foreach ($this->cameraSpecifications as $specification) {
            $camera = $this->camerasInDBMapped[Str::lower($specification['identifiers']['model'].'_'.$specification['identifiers']['brand_id'])];

            $specificationsData[] = [
                'camera_id' => $camera,
                'title' => $specification['title'],
                'value' => $specification['value'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (array_chunk($specificationsData, 200) as $batch) {
            Specification::query()->insert($batch);
        }
        unset($specificationsData, $this->cameraSpecifications);

    }

    private function insertSensorInfos(): void
    {
        $sensorInfos = [];
        foreach ($this->sensorInfo as $sensorInfo) {
            $camera = $this->camerasInDBMapped[Str::lower($sensorInfo['identifiers']['model'].'_'.$sensorInfo['identifiers']['brand_id'])];
            $sensorInfos[] = [
                'camera_id' => $camera,
                'sensor' => $sensorInfo['sensor'],
                'diagonal' => $sensorInfo['diagonal'],
                'surface_area' => $sensorInfo['surface_area'],
                'pixel_pitch' => $sensorInfo['pixel_pitch'],
                'pixel_area' => $sensorInfo['pixel_area'],
                'pixel_density' => $sensorInfo['pixel_density'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (array_chunk($sensorInfos, 100) as $batch) {
            SensorInfo::query()->insert($batch);
        }
        unset($sensorInfos);
    }

    public function removeDuplicates(): void
    {
        Specification::join('specifications as s2', function ($join) {
            $join->on('specifications.camera_id', '=', 's2.camera_id')
                ->where('specifications.id', '<', DB::raw('s2.id'));
        })
            ->whereNull('specifications.deleted_at')
            ->whereNull('s2.deleted_at')
            ->whereColumn('specifications.title', 's2.title')
            ->delete();

        SensorInfo::join('sensor_info as s2', function ($join) {
            $join->on('sensor_info.camera_id', '=', 's2.camera_id')
                ->where('sensor_info.id', '<', DB::raw('s2.id'));
        })
            ->whereNull('sensor_info.deleted_at')
            ->whereNull('s2.deleted_at')
            ->delete();
    }
}
