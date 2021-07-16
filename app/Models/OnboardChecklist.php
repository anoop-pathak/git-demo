<?php

namespace App\Models;

use App\Services\Grid\SortableTrait;
use Carbon\Carbon;

class OnboardChecklist extends BaseModel
{

    use SortableTrait;

    protected $fillable = ['title', 'action', 'is_required', 'video_url', 'section_id'];

    protected $rule = [
        'title' => 'required',
        'section_id' => 'required|exists:onboard_checklist_sections,id'
    ];

    protected function getRule()
    {
        return $this->rule;
    }

    public function setIsRequiredAttribute($value)
    {
        return $this->attributes['is_required'] = ((bool)$value);
    }

    public function getIsRequiredAttribute($value)
    {
        return (int)$value;
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function section()
    {
        return $this->belongsTo(OnboardChecklistSection::class, 'section_id');
    }
}
