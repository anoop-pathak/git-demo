<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompnayOnboardChecklist extends Model
{

    protected $table = 'company_onboard_checklist';

    public $timestamps = false;

    protected $fillable = ['company_id', 'checklist_id'];

    public function checklist()
    {
        return $this->belongsTo(OnboardChecklist::class, 'checklist_id');
    }
}
