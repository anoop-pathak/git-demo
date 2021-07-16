<?php

namespace App\Models;

use App\Services\Grid\SortableTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailLabel extends BaseModel
{

    use SortableTrait;
    use SoftDeletes;

    protected $fillable = ['name', 'company_id', 'created_by'];

    protected $rules = [
        'name' => 'required',
    ];


    protected function getRules()
    {
        return $this->rules;
    }

    public function emails()
    {
        return $this->hasMany(Email::class, 'label_id');
    }
}
