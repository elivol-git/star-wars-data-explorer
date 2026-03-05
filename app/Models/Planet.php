<?php

namespace App\Models;

use App\Models\Pivots\PivotTables;
use App\Models\Traits\NormalizeNumbers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Planet extends Model
{
    use HasFactory, NormalizeNumbers;
    protected $table = 'planets';
    protected $fillable = [
        'name',
        'rotation_period',
        'orbital_period',
        'diameter',
        'climate',
        'gravity',
        'terrain',
        'float',
        'population',
        'created',
        'edited',
        'url',
    ];

    protected $casts = [
        'population' => 'integer',
        'surface_water' => 'decimal:2',
        'rotation_period' => 'integer',
        'orbital_period' => 'integer',
        'diameter' => 'integer',
        'created' => 'datetime',
        'edited' => 'datetime',
    ];

    public function numeric(): array
    {
        return [
            'surface_water',
            'population',
            'rotation_period',
            'orbital_period',
            'diameter',
        ];
    }

    public function films(): BelongsToMany
    {
        return $this->belongsToMany(Film::class, PivotTables::FILM_PLANET);
    }

    public function people(): BelongsToMany
    {
        return $this->belongsToMany(Person::class, PivotTables::PLANET_PERSON,'planet_id','person_id');
    }

    public function searchableRelations(): array
    {
        return [
            'films',
            'films.starships',
            'films.vehicles',
            'films.species',
            'people',
            'people.starships',
            'people.vehicles',
            'people.species',
        ];
    }

    public function searchableColumns(): array
    {
        return ['name', 'climate', 'terrain', 'gravity'];
    }
}
