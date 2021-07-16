<?php
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class TaxGroupItemTax extends BaseModel
{
    use SoftDeletes;

	protected $table = 'qbd_tax_group_sales_tax';

    protected $fillable = [
        'company_id', 'group_id', 'tax_id'
    ];

    function itemSalesTax()
    {
        return $this->belongsTo(ItemSalesTax::class, 'tax_id');
    }
}