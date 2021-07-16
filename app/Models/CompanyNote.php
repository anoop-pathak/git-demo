<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyNote extends Model
{

    protected $fillable = ['company_id', 'note'];

    public function company()
    {

        return $this->belongsTo(Company::class);
    }
}
