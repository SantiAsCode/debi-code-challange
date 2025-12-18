<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Font extends Model
{
    protected $fillable = [
        'gallery_item_id',
        'name',
        'image_url',
        'foundry_url',
        'internal_url',
    ];

    public function galleryItem()
    {
        return $this->belongsTo(GalleryItem::class);
    }
}
