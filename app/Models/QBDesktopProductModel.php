<?php
namespace App\Models;

use App\Services\Grid\SortableTrait;
use Illuminate\Database\Eloquent\Model;

class QBDesktopProductModel extends Model
{
	use SortableTrait;

    protected $table = 'quickbooks_product';

	public $timestamps = false;
    protected $fillable = ['company_id', 'list_id'];

}