<?php

namespace App\Repositories;

use App\Models\EmailTemplate;
use App\Models\EmailTemplateAttachment;
use App\Models\EmailTemplateRecipient;
use App\Services\Contexts\Context;

class EmailTemplateRepository extends ScopedRepository
{


    /**
     * The base eloquent emailtemplate
     * @var Eloquent
     */
    protected $model;

    protected $scope;

    public function __construct(EmailTemplate $model, Context $scope)
    {
        $this->model = $model;
        $this->scope = $scope;
    }


    public function getFilteredEmailTemplate($filters)
    {
        $emailTemplate = $this->getEmailTemplate();
        $this->applyFilter($emailTemplate, $filters);
        return $emailTemplate;
    }

    public function saveTemplate($title, $template, $active, $createdBy, $attachments = [], $subject = null, $meta = [])
    {
        $emailTemplate = $this->model;
        $emailTemplate->title = $title;
        $emailTemplate->template = $template;
        $emailTemplate->active = $active;
        $emailTemplate->created_by = $createdBy;
        $emailTemplate->company_id = $this->scope->id();
        $emailTemplate->subject = $subject;
        $emailTemplate->stage_code = ine($meta, 'stage_code') ? $meta['stage_code'] : null;
        $emailTemplate->send_to_customer = ine($meta, 'send_to_customer');
        $emailTemplate->recipients_setting = ine($meta, 'recipients_setting') ? $meta['recipients_setting'] : null;
        $emailTemplate->save();

        $this->saveAttachments($emailTemplate, $attachments);

        if (ine($meta, 'to')) {
            $this->attachTemplateRecipient($emailTemplate, $meta['to'], 'to');
        }

        if (ine($meta, 'cc')) {
            $this->attachTemplateRecipient($emailTemplate, $meta['cc'], 'cc');
        }

        if (ine($meta, 'bcc')) {
            $this->attachTemplateRecipient($emailTemplate, $meta['bcc'], 'bcc');
        }

        return $emailTemplate;
    }

    public function updateTemplate(EmailTemplate $emailTemplate, $title, $template, $active, $attachments = [], $subject = null, $meta = [])
    {
        $emailTemplate->title = $title;
        $emailTemplate->template = $template;
        $emailTemplate->active = (bool)$active;
        $emailTemplate->subject = $subject;
        $emailTemplate->stage_code = ine($meta, 'stage_code') ? $meta['stage_code'] : null;
        $emailTemplate->send_to_customer = ine($meta, 'send_to_customer');
        $emailTemplate->recipients_setting = ine($meta, 'recipients_setting') ? $meta['recipients_setting'] : null;
        $emailTemplate->update();
        $this->saveAttachments($emailTemplate, $attachments);

        if (ine($meta, 'to')) {
            $this->attachTemplateRecipient($emailTemplate, $meta['to'], 'to');
        }

        if (ine($meta, 'cc')) {
            $this->attachTemplateRecipient($emailTemplate, $meta['cc'], 'cc');
        }

        if (ine($meta, 'bcc')) {
            $this->attachTemplateRecipient($emailTemplate, $meta['bcc'], 'bcc');
        }

        return $emailTemplate;
    }


    public function addAttachment($templateId, $attachment)
    {
        $emailTemplateAttachment = EmailTemplateAttachment::create([
            'template_id' => $templateId,
            'type' => $attachment['type'],
            'value' => $attachment['value'],
        ]);
        return $emailTemplateAttachment->resource;
    }

    /********************************Private section***********************************/

    private function applyFilter($query, $filters)
    {
        if (ine($filters, 'active')) {
            $query->where('active', '=', $filters['active']);
        }

        if (ine($filters, 'title')) {
            $query->where('title', 'like', '%' . $filters['title'] . '%');
        }

        if (ine($filters,'subject')) {
			$query->where('subject', 'like' , '%'.$filters['subject'].'%');
		}

        if (ine($filters, 'stage_code')) {
            $query->where('stage_code', $filters['stage_code']);
        }

        if(ine($filters, 'keyword')) {
			$query->keywordSearch($filters['keyword']);
		}
    }

    private function getEmailTemplate($sortable = true)
    {
        if ($sortable) {
            $emailTemplate = $this->make(['to', 'cc', 'bcc'])->Sortable();
        } else {
            $emailTemplate = $this->make(['to', 'cc', 'bcc']);
        }
        return $emailTemplate;
    }

    /**
     * Save Attachments data
     * @param $template Object | Instance of template Model
     * @param $attachments Array | array of attachments data
     * @return void
     */
    private function saveAttachments(EmailTemplate $emailTemplate, array $attachments = [])
    {
        $emailTemplate->attachments()->detach();
        if (empty($attachments)) {
            return true;
        }
        foreach ($attachments as $attachment) {
            EmailTemplateAttachment::create([
                'template_id' => $emailTemplate->id,
                'type' => $attachment['type'],
                'value' => $attachment['value'],
            ]);
        }
    }

    /**
     * Attach Email Tmplate Recipients
     * @param  object $emailTemplate Instance of email template
     * @param  array $emails array of emails
     * @param  string $type type (to, cc, bcc)
     * @return void
     */
    private function attachTemplateRecipient($emailTemplate, $emails, $type)
    {

        if ($type === 'to') {
            $emailTemplate->to()->delete();
        }

        if ($type === 'cc') {
            $emailTemplate->cc()->delete();
        }

        if ($type === 'bcc') {
            $emailTemplate->bcc()->delete();
        }

        foreach (array_filter($emails) as $email) {
            EmailTemplateRecipient::create([
                'email_template_id' => $emailTemplate->id,
                'email' => $email,
                'type' => $type
            ]);
        }
    }
}
