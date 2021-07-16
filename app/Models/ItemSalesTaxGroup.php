<?php
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class ItemSalesTaxGroup extends BaseModel
{
    use SoftDeletes;

	protected $table = 'qbd_item_sales_tax_groups';

    protected $fillable = [
        'company_id', 'qb_username', 'name', 'description', 'active',
        'qb_desktop_id', 'qb_desktop_sequence_number'
    ];

    public function rates()
    {
        return $this->hasMany('TaxGroupItemTax', 'group_id');
    }
}