<?php
namespace App\Models;

class PageTableCalculation extends BaseModel
{
	protected $fillable = ['name', 'page_type','type_id','page_id','ref_id', 'head', 'body', 'foot', 'options'];

	protected $table = 'page_table_calculations';

    const TEMPLATE_PAGE_TYPE = 'template';
    const PROPOSAL_PAGE_TYPE = 'proposal';
    const TEMP_PROPOSAL_PAGE = 'temp_proposal_page';
    const WORKSHEET_TEMPLATE_PAGE = 'worksheet_template_page';


	protected function setOptionsAttribute($value)
	{
		$this->attributes['options'] = json_encode($value);
	}

	protected function setHeadAttribute($value)
	{
		$this->attributes['head'] = json_encode($value);
	}

	protected function setBodyAttribute($value)
	{
		$this->attributes['body'] = json_encode($value);
	}

	protected function setFootAttribute($value)
	{
		$this->attributes['foot'] = json_encode($value);
	}

	protected function getOptionsAttribute($value)
	{
		return json_decode($value, true);
	}

	protected function getHeadAttribute($value)
	{
		return json_decode($value, true);
	}

	protected function getBodyAttribute($value)
	{
		return json_decode($value, true);
	}

	protected function getFootAttribute($value)
	{
		return json_decode($value, true);
	}
}