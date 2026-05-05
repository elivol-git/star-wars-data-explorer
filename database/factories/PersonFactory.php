<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Person>
 */
class PersonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'url' => 'https://swapi.dev/api/people/' . fake()->unique()->numberBetween(1, 100) . '/',
            'name' => fake()->name(),
            'height' => fake()->numberBetween(160, 210),
            'mass' => fake()->numberBetween(50, 150),
            'hair_color' => fake()->randomElement(['blond', 'brown', 'black', 'white', 'red']),
            'skin_color' => fake()->randomElement(['fair', 'tan', 'light', 'pale', 'dark']),
            'eye_color' => fake()->randomElement(['blue', 'brown', 'black', 'yellow', 'green']),
            'birth_year' => fake()->year(),
            'gender' => fake()->randomElement(['male', 'female', 'n/a']),
        ];
    }
}
