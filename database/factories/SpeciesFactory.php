<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Species>
 */
class SpeciesFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'url' => 'https://swapi.dev/api/species/' . fake()->unique()->numberBetween(1, 50) . '/',
            'name' => fake()->words(2, true),
            'classification' => fake()->randomElement(['mammal', 'amphibian', 'reptile', 'humanoid', 'robot']),
            'designation' => fake()->randomElement(['sentient', 'non-sentient', 'sapient']),
            'average_height' => fake()->numberBetween(50, 300),
            'skin_colors' => fake()->words(2, true),
            'hair_colors' => fake()->words(2, true),
            'eye_colors' => fake()->words(2, true),
            'average_lifespan' => fake()->numberBetween(50, 1000),
            'language' => 'Galactic Basic',
        ];
    }
}
