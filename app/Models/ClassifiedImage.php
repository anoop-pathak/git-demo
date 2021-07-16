<?php

namespace App\Models;

class ClassifiedImage extends BaseModel
{
    protected $table = 'classified_images';

    protected $fillable = ['classified_id', 'image', 'thumb'];
}
