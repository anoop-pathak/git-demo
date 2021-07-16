<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attachment extends Model {

	public $timestamps = false;
	protected $fillable = ['type_id', 'type', 'ref_type', 'ref_id'];


}