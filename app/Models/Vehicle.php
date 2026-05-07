<?php

namespace App\Models;

use App\Models\EntityImage;
use App\Models\Pivots\PivotTables;
use App\Models\Traits\NormalizeNumbers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Vehicle extends Model
{
    use HasFactory, NormalizeNumbers;

    protected $table = 'vehicles';
    public $timestamps = false;
    protected $appends = ['image_url'];

    protected $fillable = [
        'name',
        'model',
        'vehicle_class',
        'manufacturer',
        'cost_in_credits',
        'length',
        'crew',
        'passengers',
        'max_atmosphering_speed',
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

    protected function numeric(): array
    {
        return [
            'cost_in_credits',
            'length',
            'max_atmosphering_speed',
            'crew',
            'passengers',
            'cargo_capacity',
        ];
    }

    public function image()
    {
        return $this->hasOne(EntityImage::class, 'entity_id')
            ->where('entity_type', 'Vehicle');
    }

    public function films(): BelongsToMany
    {
        return $this->belongsToMany(Film::class, PivotTables::FILM_VEHICLE, 'vehicle_id', 'film_id');
    }

    public function pilots(): BelongsToMany
    {
        return $this->belongsToMany(Person::class, PivotTables::PERSON_VEHICLE, 'vehicle_id', 'person_id');
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
