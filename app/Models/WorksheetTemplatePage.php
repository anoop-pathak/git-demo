<?php
namespace App\Models;

class WorksheetTemplatePage extends BaseModel
{
    protected $fillable = [
        'company_id',
        'worksheet_id',
        'content',
        'auto_fill_required',
        'page_type',
        'title'
    ];

    public function pageTableCalculations()
    {
    	return $this->hasMany(PageTableCalculation::class, 'page_id')->wherePageType(PageTableCalculation::WORKSHEET_TEMPLATE_PAGE);
    }
}