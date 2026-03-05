<?php

namespace App\Models;

use App\Models\Pivots\PivotTables;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Film extends Model
{
    use HasFactory;
    protected $table = 'films';
    protected $fillable = [
        'title',
        'episode_id',
        'opening_crawl',
        'director',
        'producer',
        'release_date',
        'created',
        'edited',
        'url',
    ];

    protected $casts = [
        'episode_id' => 'integer',
        'release_date' => 'date:Y-m-d',
        'created' => 'datetime',
        'edited' => 'datetime',
    ];

    public function planets(): BelongsToMany
    {
        return $this->belongsToMany(Planet::class, PivotTables::FILM_PLANET);
    }

    public function people(): BelongsToMany
    {
        return $this->belongsToMany(Person::class, PivotTables::FILM_PERSON);
    }
    public function vehicles(): BelongsToMany
    {
        return $this->belongsToMany(Vehicle::class, PivotTables::FILM_VEHICLE);
    }

    public function species(): BelongsToMany
    {
        return $this->belongsToMany(Species::class, PivotTables::FILM_SPECIES);
    }

    public function starships(): BelongsToMany
    {
        return $this->belongsToMany(Starship::class, PivotTables::FILM_STARSHIP);
    }

    protected function releaseDate(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value
                ? \Carbon\Carbon::parse($value)->toDateString()
                : null
        );
    }

    public function searchableColumns(): array
    {
        return ['title', 'opening_crawl', 'director', 'producer'];
    }
}
