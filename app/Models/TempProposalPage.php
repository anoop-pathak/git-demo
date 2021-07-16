<?php
namespace App\Models;

class TempProposalPage extends BaseModel
{

    protected $fillable = ['company_id', 'title', 'content', 'auto_fill_required', 'page_type'];

    public $rules = [
        'content' => 'required',
    ];

    protected function getRules()
    {
        $input = \Request::all();
		$pageRules = [];

		if (ine($input,'tables')) {
			foreach ($input['tables'] as $key => $value) {
				$pageRules["tables.$key.name"] 		= 'max:30';
				$pageRules["tables.$key.ref_id"] 	= 'required';
				$pageRules["tables.$key.head"] 		= 'required';
				$pageRules["tables.$key.body"] 		= 'required';
				$pageRules["tables.$key.foot"] 		= 'required';
			}
		}

		return array_merge($this->rules, $pageRules);
    }

    public function setAutoFillRequiredAttribute($value)
    {
        if (!is_null($value)) {
            $value = json_encode($value);
        }

        $this->attributes['auto_fill_required'] = $value;
    }

    public function getAutoFillRequiredAttribute($value)
    {
        return json_decode($value, true);
    }

    public function pageTableCalculations()
    {
    	return $this->hasMany(PageTableCalculation::class, 'page_id')->wherePageType(PageTableCalculation::TEMP_PROPOSAL_PAGE);
    }
}
