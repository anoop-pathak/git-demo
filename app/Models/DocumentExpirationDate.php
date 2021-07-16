<?php

namespace App\Models;

class DocumentExpirationDate extends BaseModel
{
    protected $fillable = ['expire_date', 'company_id', 'object_id', 'object_type', 'description'];

    const PROPOSAL_OBJECT_TYPE = 'proposal';

    const ESTIMATION_OBJECT_TYPE = 'estimation';

    const RESOURCE_OBJECT_TYPE = 'resource';

    public $timestamps = false;

    protected $hidden = ['company_id', 'object_type', 'object_id'];

    protected $rules = [
        'expire_date' => 'required|date|after:today',
        'document_id' => 'required',
        'document_type' => 'required|in:proposal,estimation,resource'
    ];

    protected function getRules()
    {
        return $this->rules;
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function getObject()
    {
        try {
            $object_type = $this->object_type;
            if ($object_type == 'estimation') {
                $object_type = Estimation::class;
            } elseif ($object_type == 'proposal') {
                $object_type = Proposal::class;
            } else {
                $object_type = 'Resource';
            }
            $object = call_user_func_array($object_type . '::whereId', [$this->object_id]);
            $object = $object->firstOrFail();
        } catch (\Exception $e) {
            return false;
        }
        if (!$object) {
            return false;
        }
        return $object;
    }
}
