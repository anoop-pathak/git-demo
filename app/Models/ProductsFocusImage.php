<?php

namespace App\Models;

class ProductsFocusImage extends BaseModel
{
    protected $table = 'products_focus_images';

    protected $fillable = ['products_focus_id', 'image', 'thumb'];
}
