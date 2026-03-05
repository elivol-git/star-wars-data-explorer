<?php

namespace App\Models;

use App\Models\Pivots\PivotTables;
use App\Models\Traits\NormalizeNumbers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Species extends Model
{
    use HasFactory, NormalizeNumbers;

    protected $table = 'species';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'classification',
        'designation',
        'average_height',
        'skin_colors',
        'hair_colors',
        'eye_colors',
        'average_lifespan',
        'homeworld',
        'homeworld_id',
        'language',
        'url',
        'created',
        'edited',
    ];

    protected $casts = [
        'homeworld_id' => 'integer',
        'created' => 'datetime',
        'edited' => 'datetime',
    ];

    public function numeric(): array
    {
        return [
            'average_height',
            'average_lifespan',
        ];
    }

    public function homeworld(): BelongsTo
    {
        return $this->belongsTo(Planet::class, 'homeworld_id');
    }

    public function films(): BelongsToMany
    {
        return $this->belongsToMany(Film::class, PivotTables::FILM_SPECIES, 'species_id', 'film_id');
    }

    public function people(): BelongsToMany
    {
        return $this->belongsToMany(Person::class, PivotTables::PERSON_SPECIES, 'species_id');
    }

    public function searchableColumns(): array
    {
        return ['name'];
    }
}
