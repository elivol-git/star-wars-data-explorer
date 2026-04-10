<?php

namespace App\Services\Swapi\Relationships;

use App\Models\Film;
use App\Models\Planet;
use App\Models\Person;
use App\Models\Species;
use App\Models\Starship;
use App\Models\Vehicle;
use App\Services\Swapi\SyncFilms;
use App\Services\Swapi\SyncPeople;
use App\Services\Swapi\SyncSpecies;
use App\Services\Swapi\SyncVehicles;
use App\Services\Swapi\SyncStarships;

class SwapiRelationshipHandler
{
    public function __construct(
        protected SyncFilms $films,
        protected SyncPeople $people,
        protected SyncSpecies $species,
        protected SyncVehicles $vehicles,
        protected SyncStarships $starships,
    ) {}

    public function syncPlanetRelations(Planet $planet, array $data): void
    {
        $this->attachMany(
            $planet->films(),
            $data['films'] ?? [],
            fn ($url) => $this->films->store($url)->id
        );

        $this->attachMany(
            $planet->people(),
            $data['residents'] ?? [],
            fn ($url) => $this->people->store($url)->id
        );
    }

    public function syncFilmRelations(Film $film, array $data): void
    {
        $this->attachMany(
            $film->species(),
            $data['species'] ?? [],
            fn ($url) => $this->species->store($url)->id
        );

        $this->attachMany(
            $film->vehicles(),
            $data['vehicles'] ?? [],
            fn ($url) => $this->vehicles->store($url)->id
        );

        $this->attachMany(
            $film->starships(),
            $data['starships'] ?? [],
            fn ($url) => $this->starships->store($url)->id
        );
    }

    public function syncPersonRelations(Person $person, array $data): void
    {
        $this->attachMany(
            $person->films(),
            $data['films'] ?? [],
            fn ($url) => $this->films->store($url)->id
        );

        $this->attachMany(
            $person->species(),
            $data['species'] ?? [],
            fn ($url) => $this->species->store($url)->id
        );

        $this->attachMany(
            $person->vehicles(),
            $data['vehicles'] ?? [],
            fn ($url) => $this->vehicles->store($url)->id
        );

        $this->attachMany(
            $person->starships(),
            $data['starships'] ?? [],
            fn ($url) => $this->starships->store($url)->id
        );
    }

    public function syncSpeciesRelations(Species $species, array $data): void
    {
        $this->attachMany(
            $species->pilots(),
            $data['people'] ?? [],
            fn ($url) => $this->people->store($url)->id
        );

        $this->attachMany(
            $species->films(),
            $data['films'] ?? [],
            fn ($url) => $this->films->store($url)->id
        );
    }

    public function syncVehicleRelations(Vehicle $vehicle, array $data): void
    {
        $this->attachMany(
            $vehicle->pilots(),
            $data['pilots'] ?? [],
            fn ($url) => $this->people->store($url)->id
        );

        $this->attachMany(
            $vehicle->films(),
            $data['films'] ?? [],
            fn ($url) => $this->films->store($url)->id
        );
    }

    public function syncStarshipRelations(Starship $starship, array $data): void
    {
        $this->attachMany(
            $starship->pilots(),
            $data['pilots'] ?? [],
            fn ($url) => $this->people->store($url)->id
        );

        $this->attachMany(
            $starship->films(),
            $data['films'] ?? [],
            fn ($url) => $this->films->store($url)->id
        );
    }

    protected function attachMany($relation, array $urls, callable $resolver): void
    {
        foreach ($urls as $url) {
            $relation->syncWithoutDetaching($resolver($url));
        }
    }
}
