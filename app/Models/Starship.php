<?php

namespace App\Models;

use App\Models\EntityImage;
use App\Models\Pivots\PivotTables;
use App\Models\Traits\NormalizeNumbers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Starship extends Model
{
    use HasFactory, NormalizeNumbers;

    protected $table = 'starships';
    public $timestamps = false;
    protected $appends = ['image_url'];

    protected $fillable = [
        'name',
        'model',
        'starship_class',
        'manufacturer',
        'cost_in_credits',
        'length',
        'crew',
        'passengers',
        'max_atmosphering_speed',
        'hyperdrive_rating',
        'MGLT',
        'cargo_capacity',
        'consumables',
        'url',
        'created',
        'edited',
    ];

    protected $casts = [
        'created' => 'datetime',
        'edited' => 'datetime',
    ];

    public function numeric(): array
    {
        return [
            'cost_in_credits',
            'length',
            'max_atmosphering_speed',
            'crew',
            'passengers',
            'cargo_capacity',
            'hyperdrive_rating',
            'MGLT',
        ];
    }

    public function image()
    {
        return $this->hasOne(EntityImage::class, 'entity_id')
            ->where('entity_type', 'Starship');
    }

    public function films(): BelongsToMany
    {
        return $this->belongsToMany(Film::class, PivotTables::FILM_STARSHIP, 'starship_id', 'film_id');
    }

    public function pilots(): BelongsToMany
    {
        return $this->belongsToMany(Person::class, PivotTables::PERSON_STARSHIP, 'starship_id', 'person_id');
    }

    public function getImageUrlAttribute()
    {
        return $this->image?->image_url;
    }

    public function searchableColumns(): array
    {
        return ['name'];
    }
}
