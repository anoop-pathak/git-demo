<?php

namespace App\Models;

class Material extends BaseModel
{

    protected $table = 'materials';

    protected $fillable = ['supplier', 'name', 'unit', 'unit_cost', 'code', 'description', 'manufacturer', 'branch_id', 'branch_name', 'image_link', 'conversion_factor', 'conversion_unit', 'supplier_product_id', 'style', 'size', 'color'];

    protected $hidden = [];
}
