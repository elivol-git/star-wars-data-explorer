<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vehicle>
 */
class VehicleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'url' => 'https://swapi.dev/api/vehicles/' . fake()->unique()->numberBetween(1, 50) . '/',
            'name' => fake()->words(3, true),
            'model' => fake()->words(3, true),
            'manufacturer' => fake()->company(),
            'cost_in_credits' => fake()->numberBetween(10000, 999999999),
            'length' => fake()->randomFloat(2, 2, 50),
            'crew' => (string)fake()->numberBetween(1, 200),
            'passengers' => (string)fake()->numberBetween(0, 100),
            'cargo_capacity' => fake()->numberBetween(0, 10000000),
            'consumables' => fake()->randomElement(['1 day', '1 week', '1 month', '1 year', 'unknown']),
            'max_atmosphering_speed' => fake()->numberBetween(100, 1000),
            'vehicle_class' => fake()->randomElement(['wheeled', 'repulsorcraft', 'walker', 'airspeeder', 'ground speeder']),
        ];
    }
}
