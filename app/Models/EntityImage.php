<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntityImage extends Model
{
    protected $table = 'entity_images';

    protected $fillable = [
        'entity_type',
        'entity_id',
        'image_url',
        'source',
        'fetched_at',
    ];

    protected $casts = [
        'fetched_at' => 'datetime',
    ];
}
