<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\Grid\SortableTrait;

class CustomTax extends BaseModel
{
    use SoftDeletes;
    use SortableTrait;

    protected $fillable = ['title', 'tax_rate', 'company_id', 'quickbook_tax_code_id', 'qb_desktop_id', 'qb_desktop_sequence_number', 'created_by'];

    public function setTaxRateAttribute($value)
    {
        $this->attributes['tax_rate'] = ($value) ? $value : null;
    }
}
