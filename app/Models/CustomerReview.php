<?php

namespace App\Models;

class CustomerReview extends BaseModel
{
    protected $fillable = ['customer_id', 'job_id', 'rating', 'comment'];

    protected $rules = [
        'rating' => 'numeric|required|max:5',
    ];

    protected function getRules()
    {
        return $this->rules;
    }
}
