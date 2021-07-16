<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Laracasts\Presenter\PresentableTrait;

class Email extends Model
{

    use SoftDeletes;
    use PresentableTrait;

    protected $presenter = \App\Presenters\EmailPresenter::class;

    // email types
    const SENT = 'sent';
    const RECEIVED = 'received';
    const TRASHED = 'trashed';
    const UNREAD = 'unread';
    const UNREAD_FLAG = 0;

    // email Status
    const EMAIL_SENT = 'sent';
    const PENDING = 'pending';
    const FAILED = 'failed';
    const BOUNCED = 'bounced';

    protected $fillable = [
        'company_id',
        'subject',
        'content',
        'customer_id',
        'job_id',
        'created_by',
        'reply_to',
        'from',
        'type',
        'label_id',
    ];

    protected $dates = ['deleted_at'];

    protected $rules = [
        'to' => 'required|array|validEmailsArray',
        'cc' => 'array|validEmailsArray|nullable',
        'bcc' => 'array|validEmailsArray|nullable',
        'subject' => 'required',
        'content' => 'required',
        'attachments' => 'array|nullable',
        'main_job_id' => 'required_with:stage_code',
    ];

    protected $markAsReadRules = [
        'email_ids' => 'required|array',
        'is_read' => 'required|boolean',
    ];

    protected $applyLabelRules = [
        'thread_ids' => 'required|array',
        'label_id' => 'required',
    ];

    protected function getRules()
    {
        return $this->rules;
    }

    protected function getMarkAsReadRules()
    {
        return $this->markAsReadRules;
    }

    protected function getApplyLabelRules()
    {
        return $this->applyLabelRules;
    }

    public function recipients()
    {
        return $this->hasMany(EmailRecipient::class);
    }

    public function recipientsTo()
    {
        return $this->recipients()->where('type', 'to');
    }

    public function recipientsCc()
    {
        return $this->recipients()->where('type', 'cc');
    }

    public function recipientsBcc()
    {
        return $this->recipients()->where('type', 'bcc');
    }

    public function attachments()
    {
        return $this->belongsToMany(Resource::class, 'email_attachments', 'email_id', 'value');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function label()
    {
        return $this->belongsTo(EmailLabel::class);
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    //jobs
    public function jobs()
    {
        return $this->belongsToMany(Job::class, 'email_job', 'email_id', 'job_id');
    }

    public function replies()
    {
        return $this->hasMany(Email::class, 'reply_to', 'id')->withTrashed();
    }

    public function thread()
    {
        return $this->hasMany(Email::class, 'conversation_id', 'conversation_id');
    }

    public function parentMail()
    {
        return $this->belongsTo(Email::class, 'reply_to', 'id');
    }

    public function scopeSent($query)
    {
        $query->where('emails.type', self::SENT);
    }

    public function scopeReceived($query)
    {
        $query->where('emails.type', self::RECEIVED);
    }

    // public function getThreadCount()
    // {
    // 	return $this->getThread()->count();
    // }

    public function scopeGetThreadCount($query)
    {
        $query->addSelect(DB::raw('(select count(id) from emails as thread where thread.conversation_id = emails.conversation_id) as thread_count'));
    }

    public function getThread()
    {
        $thread = self::whereConversationId($this->conversation_id)
            ->orderBy('created_at', 'desc');

        if ($this->deleted_at) {
            $thread->onlyTrashed();
        }
        return $thread->get();
    }

    public function getRecursiveThreadIds()
    {
        $ids = $this->recursivly();
        return $ids;
    }

    private function recursivly()
    {
        $ids[] = $this->id;
        $replies = $this->replies->pluck('id')->toArray();
        $ids = array_merge($ids, $replies);
        foreach ($this->replies as $reply) {
            if ($reply->replies) {
                $replies = $reply->recursivly();
                $ids = array_merge($replies, $ids);
            }
        }
        return array_unique($ids);
    }
}
