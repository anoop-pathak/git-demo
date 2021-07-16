<?php

namespace App\Models;

use App\Services\Grid\SortableTrait;

class CompanyTrade extends BaseModel
{

    use SortableTrait;

    protected $table = 'company_trade';

    protected $fillable = ['trade_color'];

    public $timestamps = false;

    protected $rules = [
        'trade_id' => 'required',
        'color' => 'required'
    ];

    protected function getRules()
    {
        return $this->rules;
    }

    public function trade()
	{
		return $this->belongsTo(Trade::class);
	}
}
