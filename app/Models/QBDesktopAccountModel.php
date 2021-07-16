<?php
namespace App\Models;

use  App\Services\Grid\SortableTrait;
use Illuminate\Database\Eloquent\Model;

class QBDesktopAccountModel extends Model
{
    protected $table = 'quickbooks_account';

    use SortableTrait;

	public $timestamps = false;
	protected $fillable = ['list_id'];
}