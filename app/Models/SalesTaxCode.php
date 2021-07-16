<?php
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class SalesTaxCode extends BaseModel
{
    use SoftDeletes;

	protected $table = 'qbd_sales_tax_codes';

    protected $fillable = [
        'company_id', 'qb_username', 'name',
        'type', 'description', 'active',
        'taxable', 'qb_desktop_id', 'qb_desktop_sequence_number'
    ];
}