<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SensorInfo>
 */
class SensorInfoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstDiagonal = $this->faker->randomFloat(2, 10, 30);
        $secondDiagonal = $this->faker->randomFloat(2, 10, 30);

        return [
            //23.5 x 15.7 etc
            'sensor' => $firstDiagonal.' x '.$secondDiagonal,
            //28.26 etc
            'diagonal' => round(sqrt(pow($firstDiagonal, 2) + pow($secondDiagonal, 2)), 2),
            //369 mm²
            'surface_area' => round(($firstDiagonal * $secondDiagonal), 2),
            'pixel_pitch' => $firstDiagonal, // over resolution normally
            'pixel_area' => function ($attributes) {
                return round($attributes['pixel_pitch'] * $attributes['pixel_pitch'], 2);
            },
            'pixel_density' => function ($attributes) {
                return $attributes['pixel_pitch'];
            },
        ];
    }
}
