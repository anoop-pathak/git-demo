<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplatePage extends Model
{
    protected $fillable = ['template_id', 'content', 'editable_content', 'image', 'thumb', 'order', 'auto_fill_required'];

    public function setAutoFillRequiredAttribute($value)
    {
        if (!is_null($value)) {
            $value = json_encode($value);
        }

        $this->attributes['auto_fill_required'] = $value;
    }

    public function getAutoFillRequiredAttribute($value)
    {
        return json_decode($value, true);
    }

    public function template()
    {
        return $this->belongsTo(Template::class);
    }

    public function scopeCompany($query, $companyId)
    {
        $query->join('templates', 'templates.id', '=', 'template_pages.template_id')
            ->where(function ($query) use ($companyId) {
                $query->where('company_id', $companyId)->orWhereNull('company_id');
            })->whereNull('templates.deleted_at')
            ->select('template_pages.*');
    }

    public function pageTableCalculations()
    {
    	return $this->hasMany(PageTableCalculation::class, 'page_id')->wherePageType(PageTableCalculation::TEMPLATE_PAGE_TYPE);
    }
}
