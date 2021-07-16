<?php

namespace App\Models;

class TierLibrary extends BaseModel
{
    protected $table = 'tiers_library';

    protected $fillable = ['company_id', 'name'];

    protected function getRules($id = null)
	{
		$rules = [
			'name' => 'required|unique:tiers_library,name,'.$id.',id,company_id,'.getScopeId(),
		];

		return $rules;
	}
}
