<?php

namespace App\Models;

class CustomerFeedback extends BaseModel
{
    protected $presenter = \App\Presenters\AddressPresenter::class;

    protected $fillable = ['company_id', 'job_id', 'customer_id', 'subject', 'description', 'type',];

    protected $hidden = ['company_id',];

    protected $rules = [
        'share_token' => 'required',
        'subject' => 'required',
        'description' => 'required',
        'type' => 'required|in:testimonial,complaint',
    ];

    protected function getRules()
    {
        return $this->rules;
    }
}
