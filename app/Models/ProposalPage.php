<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProposalPage extends Model
{

    protected $fillable = ['template', 'template_cover', 'image', 'thumb', 'proposal_id', 'order', 'title', 'auto_fill_required'];

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

    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }

    public function scopeCompany($query, $companyId)
    {
        $query->join('proposals', 'proposals.id', '=', 'proposal_pages.proposal_id')
            ->where('proposals.company_id', $companyId)
            ->whereNull('proposals.deleted_at')
            ->select('proposal_pages.*');
    }

    public function pageTableCalculations()
    {
    	return $this->hasMany(PageTableCalculation::class, 'page_id')->wherePageType(PageTableCalculation::PROPOSAL_PAGE_TYPE);
    }
}
