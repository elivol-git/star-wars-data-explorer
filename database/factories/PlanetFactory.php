<?php

namespace Database\Factories;

use App\Models\Planet;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanetFactory extends Factory
{
    protected $model = Planet::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'rotation_period' => $this->faker->numberBetween(10, 50),
            'orbital_period' => $this->faker->numberBetween(100, 500),
            'diameter' => $this->faker->numberBetween(1000, 15000),
            'climate' => $this->faker->word(),
            'gravity' => $this->faker->word(),
            'terrain' => $this->faker->word(),
            'surface_water' => $this->faker->numberBetween(0, 100),
            'population' => $this->faker->numberBetween(0, 2000000000),
            'url' => 'https://swapi.dev/api/planets/' . $this->faker->randomNumber(),
        ];
    }
}
