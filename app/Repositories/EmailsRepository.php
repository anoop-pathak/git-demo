<?php

namespace App\Repositories;

use App\Models\Email;
use App\Models\EmailAttachment;
use App\Models\EmailRecipient;
use App\Services\Contexts\Context;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class EmailsRepository extends ScopedRepository
{

    /**
     * The base eloquent email
     * @var Eloquent
     */
    protected $model;
    protected $scope;

    function __construct(Email $model, Context $scope)
    {
        $this->model = $model;
        $this->scope = $scope;
    }

    /**
     * Save Email
     * @param $type String | 'sent' or 'received'
     * @param $subject String | Subject of Mail
     * @param $content Text | Content of Mail
     * @param $from String | From Address
     * @param $to Array | Array of emails to
     * @param $cc Array | Array of emails cc
     * @param $bcc Array | Array of emails bcc
     * @param $attachments Array | Array of attachments
     * @param $replyTo Int | Email Id (If it is a reply of an existing mail)
     * @param $createdBy Integer | Current user id (who sending this email)
     * @param $meta Array | Array of meta data (customer_id, job_id)
     * @return object
     */
    public function save($type, $subject, $content, $fromAddress, $to, $cc = [], $bcc = [], $attachments = [], $createdBy, $meta = [])
    {
        $companyScope = false;
        $conversationId = null;
        $status = Email::PENDING;

        if ($this->scope->has()) {
            $companyScope = $this->scope->id();
        }

        //associate parent mail data in case of reply
        if (ine($meta, 'reply_to')
            && ($parentMail = Email::find($meta['reply_to']))) {
            if (!ine($meta, 'customer_id')) {
                $meta['customer_id'] = $parentMail->customer_id;
            }

            if (!ine($meta, 'customer_id')) {
                $meta['stage_code'] = $parentMail->stage_code;
            }

            if (!ine($meta, 'job_id')) {
                $meta['job_id'] = $parentMail->jobs->pluck('id')->toArray();
            }
            $conversationId = $parentMail->conversation_id;
            //status clear for recieved mails.
            $status = null;

            // set subject for reply if empty..
            if (empty($subject)) {
                $subject = 'Re: ' . $parentMail->subject;
            }
        }

        if (empty($conversationId)) {
            $conversationId = Carbon::now()->timestamp . rand();
        }

        $email = new Email;
        $email->type = $type;
        $email->from = $fromAddress;
        $email->subject = $subject;
        $email->content = !empty($content) ? $content : "";
        $email->company_id = $companyScope;
        $email->customer_id = ine($meta, 'customer_id') ? $meta['customer_id'] : null;
        $email->created_by = $createdBy; //not tracked in reply
        $email->reply_to = ine($meta, 'reply_to') ? $meta['reply_to'] : null;
        $email->mailbox_id = ine($meta, 'mailbox_id') ? $meta['mailbox_id'] : null;
        $email->stage_code = ine($meta, 'stage_code') ? $meta['stage_code'] : null;
        $email->conversation_id = $conversationId;
        $email->status = $status;
        $email->save();

        $this->saveRecipients($email, $to, $cc, $bcc);
        $this->saveAttachments($email, $attachments);

        // link jobs
        if (ine($meta, 'job_id')) {
            $this->saveJobs($email, array_filter((array)$meta['job_id']));
        }

        return $email;
    }

    /**
     * Get List of sent emails
     * @param $filters Array | filters for emails listing
     * @return Object
     */
    public function getEmails($filters = [])
    {

        // $emails = $this->make(['customer', 'jobs','attachments', 'replies'])
        // ->orderBy('id','desc')
        // ->groupBy('conversation_id')
        // ->select('emails.*');

        //prepare sub query..
        $sub = $this->make()->selectRaw('max(id) as id')
            ->groupBy('conversation_id')
            ->orderBy('id', 'desc');

        if (ine($filters, 'type') && $filters['type'] === Email::TRASHED) {
            $sub->onlyTrashed();
        }

        if (ine($filters, 'label_id')) {
            $sub->where('label_id', $filters['label_id']);
        } else {
            if ((ine($filters, 'not_moved') || !ine($filters, 'type') || ($filters['type'] === Email::RECEIVED)) && !ine($filters, 'job_id')) {
                $sub->where('is_moved', false);
            }
        }

        $sub = generateQueryWithBindings($sub);

        $emails = $this->make([
            'jobs.customer',
            'jobs.customer.phones',
            'jobs.address',
            'jobs.address.state',
            'jobs.address.country',
            'jobs.projects',
            'jobs.jobMeta',
            'jobs.company.subscriberResource',
            'jobs.workflow',
            'customer.phones',
            'attachments.meta',
            'createdBy',
            'recipientsTo',
            'recipientsCc',
            'recipientsBcc',
            'label',
        ])->join(DB::raw("({$sub}) as sub"), 'sub.id', '=', 'emails.id')
            ->orderBy('id', 'desc')
            ->select('emails.*');

        $emails->getThreadCount();

        $this->applyFilters($emails, $filters);
        return $emails;
    }

    public function getUnreadEmailCount()
	{
		$query = $this->make()
			->select('emails.id', 'emails.conversation_id')
			->whereIn('emails.id', function($subQuery) {
				$subQuery->selectRaw('max(id) as id')
					->from('emails')
					->where('created_by', Auth::id())
					->where('company_id', getScopeId())
					->groupBy('conversation_id');
			})
			->leftJoin('emails as received_emails', function($join) {
				$join->on('emails.conversation_id', '=', 'received_emails.conversation_id');
				$join->where('received_emails.type', '=', 'received');
			})
			->whereNotNull('received_emails.id')
			->where('emails.is_read', Email::UNREAD_FLAG)
			->where('emails.created_by', Auth::id())
			->where('emails.is_moved', false);

		return $query->distinct('emails.conversation_id')->count('emails.conversation_id');
	}

    public function getById($id, array $with = [])
    {
        $query = $this->make($with);
        $query->select('*');
        $query->getThreadCount();

        return $query->withTrashed()->findOrFail($id);
    }

    /***************** Private section ******************/

    /**
     * Save Recipients of mail
     * @param $email Object | Instance of Email Model
     * @param $to Array | array of 'to' emails address
     * @param $cc Array | array of 'cc' emails address
     * @param $bcc Array | array of 'bcc' emails address
     * @return void
     */
    private function saveRecipients(Email $email, array $to, array $cc = [], array $bcc = [])
    {
        $emailId = $email->id;

        // to recipients
        $to = arry_fu($to);
        foreach ($to as $email) {
            EmailRecipient::create([
                'email_id' => $emailId,
                'email' => $email,
                'type' => 'to',
            ]);
        }

        // cc recipients
        $cc = arry_fu($cc);
        foreach ($cc as $email) {
            EmailRecipient::create([
                'email_id' => $emailId,
                'email' => $email,
                'type' => 'cc',
            ]);
        }

        // bcc recipients
        $cc = arry_fu($bcc);
        foreach ($bcc as $email) {
            EmailRecipient::create([
                'email_id' => $emailId,
                'email' => $email,
                'type' => 'bcc',
            ]);
        }
    }

    /**
     * Save Attachments data
     * @param $email Object | Instance of Email Model
     * @param $attachments Array | array of attachments data
     * @return void
     */
    private function saveAttachments(Email $email, array $attachments = [])
    {
        foreach ($attachments as $attachment) {
            EmailAttachment::create([
                'email_id' => $email->id,
                'type' => $attachment['type'],
                'value' => $attachment['value'],
            ]);
        }
    }

    /**
     * Apply filters
     * @param $query Object | Query Builder
     * @param $filters Array | filters for emails listing
     * @return void
     */
    private function applyFilters($query, $filters = [])
    {
        // not apply users filter if All filters is applied
        if (ine($filters, 'users') && is_array($filters['users']) && ($filters['users'] !== 'all')) {
            $query->whereIn('emails.created_by', (array)$filters['users']);
        }

        if (ine($filters, 'customer_id')) {
            $query->where('emails.customer_id', $filters['customer_id']);
        }

        if (ine($filters, 'stage_code')) {
            $query->where('emails.stage_code', $filters['stage_code']);
        }

        if (ine($filters, 'job_id')) {
            $query->whereHas('Jobs', function ($query) use ($filters) {
                $query->whereIn('job_id', (array)$filters['job_id']);
            });
        }

        if (ine($filters, 'subject')) {
            $query->where('emails.subject', 'Like', '%' . $filters['subject'] . '%');
        }

        if (ine($filters, 'with_reply')) {
            $companyId = $this->getScopeId();
            $query->join(DB::raw("(select conversation_id from emails where type ='received' and company_id={$companyId}) as thread"), 'thread.conversation_id', '=', 'emails.conversation_id')->groupBy('emails.id');
        }

        if (ine($filters, 'type') && $filters['type'] === Email::RECEIVED) {
            $query->where('emails.type', Email::RECEIVED);
        }

        if (ine($filters, 'type') && $filters['type'] === Email::TRASHED) {
            $query->onlyTrashed();
        }

        if (ine($filters, 'type') && $filters['type'] === Email::SENT) {
            $query->sent();
        }

        if (ine($filters, 'type') && $filters['type'] === Email::UNREAD) {
            $query->where('emails.is_read', Email::UNREAD_FLAG);
        }

        if (ine($filters, 'to')) {
            $query->whereIn('emails.id', function ($query) use ($filters) {
                $query->select('email_id')->from('email_recipient')
                    ->where('email', 'Like', '%' . $filters['to'] . '%')
                    ->whereType('to');
            });
        }

        if (ine($filters, 'from')) {
            $query->where(function ($query) use ($filters) {
                $query->where('emails.from', 'Like', '%' . $filters['from'] . '%')
                    ->orWhereIn('emails.id', function ($query) use ($filters) {
                        $query->select('reply_to')->from('emails')
                            ->where('from', 'Like', '%' . $filters['from'] . '%');
                    });
            });
        }

        if (ine($filters, 'keyword')) {
            $query->where(function ($query) use ($filters) {
                $query->where('emails.subject', 'Like', '%' . $filters['keyword'] . '%');

                $query->orWhere(function ($query) use ($filters) {
                    $query->where('emails.from', 'Like', '%' . $filters['keyword'] . '%')
                        ->orWhereIn('emails.id', function ($query) use ($filters) {
                            $query->select('reply_to')->from('emails')
                                ->where('from', 'Like', '%' . $filters['keyword'] . '%');
                        });
                });

                $query->orWhereIn('emails.id', function ($query) use ($filters) {
                    $query->select('email_id')->from('email_recipient')
                        ->where('email', 'Like', '%' . $filters['keyword'] . '%')
                        ->whereType('to');
                });
            });
        }

        //exclude replies in as new mail
        // $query->whereNull('emails.reply_to');
    }

    private function saveJobs($email, $jobIds)
    {
        $email->jobs()->detach();
        if (!empty($jobIds)) {
            sort($jobIds);
            $email->jobs()->attach($jobIds);

            $jobs = $email->jobs;
            foreach ($jobs as $job) {
				$job->updateJobUpdatedAt();
			}
        }
        return $email;
    }
}
