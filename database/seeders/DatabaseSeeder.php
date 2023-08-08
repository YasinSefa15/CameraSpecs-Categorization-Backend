<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Brand;
use App\Models\Camera;
use App\Models\Specification;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        Brand::factory(5)
            ->create()
            ->each(function ($brand) {
                Camera::factory(fake()->numberBetween(50, 300))
                    ->create([
                        'brand_id' => $brand->id,
                    ])
                    ->each(function ($camera) {
                        $camera->images()->save(\App\Models\Image::factory()->make([
                            'taggable_type' => 'App\Models\Camera',
                        ]));

                        $camera->sensor_info()->save(\App\Models\SensorInfo::factory()->make([
                            'camera_id' => $camera->id,
                        ]));

                        $specifications = [];

                        $specifications[] = [
                            'title' => fake()->title,
                            'value' => 'x',
                            'camera_id' => $camera->id,
                        ];

                        //$camera->specifications()->save(\App\Models\Specification::query()->insert($specifications));
                        Specification::query()->insert($specifications);
                    });

                $brand->images()->save(\App\Models\Image::factory()->make([
                    'taggable_type' => 'App\Models\Brand',
                ]));
            });
    }
}
