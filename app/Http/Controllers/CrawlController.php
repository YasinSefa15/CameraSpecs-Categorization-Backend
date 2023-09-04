<?php

namespace App\Http\Controllers;

use App\Http\Services\BrandCrawlService;
use App\Http\Services\CameraCrawlService;
use App\Models\Brand;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CrawlController extends Controller
{
    public function brands(Request $request, BrandCrawlService $brandCrawl): \Illuminate\Http\JsonResponse
    {
        //to rollback changes when there is an error
        //DB::beginTransaction();
        DB::enableQueryLog();
        try {
            $url = 'https://www.digicamdb.com/cameras/';
            $brandCrawl->getBrandsAndImages($url, $request->getUri());

            //checking get same number of images and brands
            if (! $brandCrawl->imageCountAndBrandCountMatch()) {
                return response()->json([
                    'message' => 'Fotoğraf sayısı ile marka sayısı aynı değil',
                ], 400);
            }

            //fetched brands and images now can insert the database
            $brandCrawl->insertBrandsToDatabase();

            //$brandCrawl->brandCameras();

            //DB::commit();

            return response()->json([
                'message' => 'Markalar başarılı bir şekilde çekildi',
                'total_query' => count(DB::getQueryLog()),
            ], 201);
        } catch (Exception $e) {
            //DB::rollback();

            return response()->json([
                'message' => 'Bir hata meydana geldi',
                'error' => $e->getMessage(),
                'trace' => $e->getTrace(),
            ], 500);
        }
    }

    public function cameras(Request $request, CameraCrawlService $crawlService)
    {
        //to rollback changes when there is an error
        //DB::beginTransaction();
        DB::enableQueryLog();
        try {
            $brands = Brand::query()->select('name')->get()->pluck('name')->toArray();
            $brandNames = array_map('strtolower', $brands);

            $crawlService->brandNameList = $brandNames;
            $crawlService->requestUrl = Str::before($request->getUri(), 'api');
            $crawlService->brandCameras();
            $crawlService->insertCameraInfos();
            //$crawlService->insertCameraImagesToDB();

            //DB::commit();

            return response()->json([
                'message' => 'Kameralar başarılı bir şekilde çekildi',
                'total_query' => count(DB::getQueryLog()),
            ], 201);
        } catch (Exception $e) {
            //DB::rollback();

            return response()->json([
                'message' => 'Bir hata meydana geldi',
                'error' => $e->getMessage(),
                'trace' => $e->getTrace(),
            ], 500);
        }
    }
}
