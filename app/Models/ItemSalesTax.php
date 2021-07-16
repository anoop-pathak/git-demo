<?php
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class ItemSalesTax extends BaseModel
{
    use SoftDeletes;

	protected $table = 'qbd_item_sales_taxes';

    protected $fillable = [
        'company_id', 'qb_username', 'name',
        'type', 'description', 'active',
        'tax_rate', 'qb_vendor_id', 'qb_desktop_id',
        'qb_desktop_sequence_number'
    ];
}