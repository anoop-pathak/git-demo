<?php

namespace App\Models;

class TemplateUse extends BaseModel
{

    protected $fillable = ['company_id', 'template_id', 'type'];

    const PROPOSAL = 'proposal';
    const ESTIMATE = 'estimate';
}
