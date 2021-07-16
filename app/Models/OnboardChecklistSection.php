<?php

namespace App\Models;

use App\Services\Grid\SortableTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

class OnboardChecklistSection extends BaseModel
{
    use SortableTrait;
    use SoftDeletes;

    protected $fillable = ['title', 'position'];

    protected $rule = [
        'title' => 'required|unique:onboard_checklist_sections,title'
    ];

    protected $updateRule = [
        'title' => 'required|unique:onboard_checklist_sections,title'
    ];

    protected function getRule()
    {
        return $this->rule;
    }

    protected function getUpdateRule($id)
    {
        $rule = $this->updateRule;
        $rule['title'] .= ',' . $id;

        return $rule;
    }

    public function checklists()
    {
        return $this->hasMany(OnboardChecklist::class, 'section_id');
    }
}
