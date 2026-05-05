<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Starship>
 */
class StarshipFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'url' => 'https://swapi.dev/api/starships/' . fake()->unique()->numberBetween(1, 50) . '/',
            'name' => fake()->words(3, true),
            'model' => fake()->words(3, true),
            'manufacturer' => fake()->company(),
            'cost_in_credits' => fake()->numberBetween(1000000, 999999999),
            'length' => fake()->randomFloat(2, 10, 2000),
            'crew' => (string)fake()->numberBetween(2, 1000),
            'passengers' => (string)fake()->numberBetween(0, 500),
            'cargo_capacity' => fake()->numberBetween(0, 50000000),
            'consumables' => fake()->randomElement(['1 month', '1 year', '2 years', '5 years', 'unknown']),
            'hyperdrive_rating' => fake()->randomFloat(1, 0.5, 4.0),
            'MGLT' => fake()->numberBetween(60, 1000),
            'starship_class' => fake()->randomElement(['Star Destroyer', 'Light transport', 'Assault ship', 'Cargo ship']),
        ];
    }
}
