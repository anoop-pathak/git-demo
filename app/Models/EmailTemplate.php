<?php

namespace App\Models;

use App\Services\Grid\SortableTrait;
use Nicolaslopezj\Searchable\SearchableTrait;

class EmailTemplate extends BaseModel
{

    use SortableTrait;
    use SearchableTrait;

    protected $fillable = [
        'company_id',
        'created_by',
        'active',
        'template',
        'title',
        'subject',
        'stage_code',
        'send_to_customer',
        'recipients_setting',
    ];

    protected $rule = [
        'template' => 'required',
        'title' => 'required',
        'attachments' => 'array|nullable',
        'to' => 'array|nullable',
        'cc' => 'array|nullable',
        'bcc' => 'array|nullable',
        'send_to_customer' => 'boolean|nullable',
        'recipients_setting' => 'array|nullable'
    ];

    protected $deleteFileRule = [
        'resource_id' => 'required|exists:template_attachments,value',
        'template_id' => 'required|exists:template_attachments,template_id',
    ];

    protected $attachFileRule = [
        'template_id' => 'required|exists:email_templates,id',
        'type' => 'required|in:proposal,estimate,resource,file',
        'value' => 'required_if:type,proposal,estimate,resource',
        'file' => 'required_if:type,file'
    ];

    protected function getRules()
    {
        return $this->rule;
    }

    protected function getDeleteFileRule()
    {
        return $this->deleteFileRule;
    }

    protected function getAttachFileRule()
    {
        return $this->attachFileRule;
    }

    public function attachments()
    {
        return $this->belongsToMany(Resource::class, 'template_attachments', 'template_id', 'value');
    }

    public function setRecipientsSettingAttribute($value)
    {
        $setting = [
            'to' => [],
            'cc' => [],
            'bcc' => []
        ];

        if (is_array($value)) {
            $setting = array_merge($setting, $value);
        }

        return $this->attributes['recipients_setting'] = json_encode($setting);
    }

    public function getRecipientsSettingAttribute($value)
    {
        return json_decode($value);
    }

    public function stage()
    {
        return $this->belongsTo(WorkflowStage::class, 'stage_code', 'code')->orderBy('id', 'desc');
    }

    public function cc()
    {
        return $this->hasMany(EmailTemplateRecipient::class, 'email_template_id')->whereType('cc');
    }

    public function bcc()
    {
        return $this->hasMany(EmailTemplateRecipient::class, 'email_template_id')->whereType('bcc');
    }

    public function to()
    {
        return $this->hasMany(EmailTemplateRecipient::class, 'email_template_id')->whereType('to');
    }

    public function recipient()
    {
        return $this->hasMany(EmailTemplateRecipient::class, 'email_template_id');
    }

    public function scopeKeywordSearch($query, $keyword)
	{
		$this->searchable = [
			'columns' => [
				'email_templates.title' => 10,
				'email_templates.subject' => 10,
			],
		];

 		$query->search(implode(' ', array_slice(explode(' ', $keyword), 0, 10)), null, true);
	}
}
