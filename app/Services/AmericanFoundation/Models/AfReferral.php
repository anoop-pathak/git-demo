<?php
namespace App\Services\AmericanFoundation\Models;

use App\Models\BaseModel;

class AfReferral extends BaseModel
{

    protected $table = "af_referrals";

    protected $fillable = [
        'company_id', 'group_id', 'user_id', 'af_id', 'referral_id', 'name', 'options', 'csv_filename'
    ];
}