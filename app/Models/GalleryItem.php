<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GalleryItem extends Model
{
    protected $fillable = [
        'external_id',
        'title',
        'year',
        'image_url',
        'contributor',
        'designers',
    ];

    protected $casts = [
        'designers' => 'array',
    ];

    public function fonts()
    {
        return $this->hasMany(Font::class);
    }
}
