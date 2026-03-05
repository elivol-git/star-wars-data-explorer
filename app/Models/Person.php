<?php

namespace App\Models;

use App\Models\Pivots\PivotTables;
use App\Models\Traits\NormalizeNumbers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Person extends Model
{
    use HasFactory, NormalizeNumbers;

    protected $table = 'people';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'height',
        'mass',
        'hair_color',
        'skin_color',
        'eye_color',
        'birth_year',
        'gender',
        'homeworld',
        'homeworld_id',
        'created',
        'edited',
        'url',
    ];

    protected $casts = [
        'homeworld_id' => 'integer',
        'created' => 'datetime',
        'edited' => 'datetime',
    ];

    public function numeric(): array
    {
        return [
            'height',
            'mass',
        ];
    }

    public function homeworld(): BelongsTo
    {
        return $this->belongsTo(Planet::class, 'homeworld_id');
    }

    public function planets(): BelongsToMany
    {
        return $this->belongsToMany(
            Planet::class,
            PivotTables::PLANET_PERSON,
            'person_id',
            'planet_id'
        );
    }
    public function films(): BelongsToMany
    {
        return $this->belongsToMany(Film::class, PivotTables::FILM_PERSON);
    }

    public function species(): BelongsToMany
    {
        return $this->belongsToMany(Species::class, PivotTables::PERSON_SPECIES);
    }

    public function vehicles(): BelongsToMany
    {
        return $this->belongsToMany(Vehicle::class, PivotTables::PERSON_VEHICLE);
    }

    public function starships(): BelongsToMany
    {
        return $this->belongsToMany(Starship::class, PivotTables::PERSON_STARSHIP);
    }

    public function searchableColumns(): array
    {
        return ['name'];
    }
}
