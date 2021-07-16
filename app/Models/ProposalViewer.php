<?php
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\Grid\SortableTrait;

class ProposalViewer extends BaseModel
{
	use SortableTrait;
	use SoftDeletes;
	protected $table = 'proposal_viewers';
	protected $fillable = ['company_id', 'title', 'description', 'display_order', 'is_active'];
	protected $rules = [
		'title' 		=> 'required',
		'is_active'		=> 'required',
	];
	protected function getProposalRules()
	{
		return $this->rules;
	}
	public function scopeActive($query)
	{
		$query->whereIsActive(true);
	}
}