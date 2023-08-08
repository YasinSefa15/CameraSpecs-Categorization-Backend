<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Brand>
 */
class BrandFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => Str::substr($this->faker->company(), 0, 32),
            'headquarters' => Str::substr($this->faker->country(), 0, 32),
            'is_major' => $this->faker->boolean(40),
        ];
    }
}
