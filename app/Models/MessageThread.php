<?php

namespace App\Models;
use App\Services\Grid\DivisionTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

class MessageThread extends BaseModel
{
    use DivisionTrait;
    use SoftDeletes;

    protected $fillable = ['job_id', 'id', 'company_id', 'participant', 'participant_setting', 'type', 'phone_number', 'created_by'];

    public $incrementing = false;

    const TYPE_SMS = 'SMS';
	const TYPE_SYSTEM_MESSAGE = 'SYSTEM_MESSAGE';
	const USER_PARTICIPANTS = 'user';
	const CUSTOMER_PARTICIPANTS = 'customer';

    public function participants()
    {
        return $this->belongsToMany(User::class, 'message_thread_participants', 'thread_id', 'user_id')
            ->select('users.id', 'users.first_name', 'users.last_name', 'users.group_id', 'users.email', 'users.color');
    }

    public function userParticipants()
	{
		return $this->belongsToMany(User::class, 'message_thread_participants', 'thread_id', 'ref_id')
			->where('ref_type', self::USER_PARTICIPANTS)
            ->select('users.id', 'users.first_name', 'users.last_name', 'users.group_id', 'users.email', 'users.color');
    }

    public function customerParticipants()
	{
		return $this->belongsToMany(Customer::class, 'message_thread_participants', 'thread_id', 'ref_id')
			->where('ref_type', self::CUSTOMER_PARTICIPANTS);
	}

    public function tags()
	{
		return $this->belongsToMany(Tag::class, 'thread_tag', 'thread_id', 'tag_id')->withTimestamps();
	}

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id')->select('id', 'first_name', 'last_name', 'group_id', 'color');
    }

    public function customer()
	{
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function messageStatuses()
    {
        return $this->hasMany(MessageStatus::class, 'thread_id', 'id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'thread_id');
    }

    public function messageStatus()
	{
		return $this->hasMany(MessageStatus::class, 'thread_id');
    }

    public function unreadMessageCount()
	{
		return $this->messageStatus()->where('status', Message::UNREAD)
			->selectRaw('COUNT(message_status.id) as count, thread_id')
			->groupBy('message_status.thread_id');
	}

    public function job()
    {
        return $this->belongsTo(Job::class)->withTrashed();
    }

    public function createdBy()
	{
        return $this->belongsTo(User::class, 'created_by')->select('id', 'first_name', 'last_name', 'group_id', 'color');
    }

    public function setParticipantSettingAttribute($value)
	{
		return $this->attributes['participant_setting'] = $value ? json_encode($value) : null;
	}

	public function getParticipantSettingAttribute($value)
	{
		return json_decode($value);
	}

    //join thread participants
    public function scopeAttachUserMessages($query, $userId, $type = null)
    {
        $query->join('message_thread_participants', 'message_thread_participants.thread_id', '=', 'message_threads.id');
        if($type == self::TYPE_SMS) {
			$query->where('message_thread_participants.ref_type', self::USER_PARTICIPANTS)
				->where('message_thread_participants.ref_id', $userId);
		} else {
			$query->where('message_thread_participants.user_id', $userId);
		}
    }

    /**
     * scope participants
     * @param  $query
     * @param  $participantIds
     * @return query
     */
    public function scopeParticipants($query, $participantIds)
    {
        $query->where(function($query) use($participantIds) {
            $query->whereIn('message_threads.id', function($query) use($participantIds) {
                $query->select('message_thread_participants.thread_id')
                    ->from('message_thread_participants')
                    ->whereIn('message_thread_participants.user_id', (array) $participantIds);
            });
        });
    }

    public function scopeUnreadThread($query, $userId)
	{
		$query->whereIn('message_threads.id', function($query) use($userId) {
			$query->select('message_status.thread_id')
			->from('message_status')
			->where('message_status.status', 1)
			->where('message_status.user_id', $userId);
		});
	}

    //add unread message status of current user
    public function scopeAttachUserUnreadMessageCount($query, $userId)
    {
        $query->with(['unreadMessageCount' => function($query) use($userId){
			$query->where('user_id', $userId);
		}]);
    }

    // one to many search
    public function scopeOneToManySearch($query, $currentUserId, $participants)
    {
        $participants[] = $currentUserId;
        $participants = arry_fu($participants);
        sort($participants);
        // $participant = implode('%', $participants);

        foreach ($participants as $key => $participant) {
            $query->leftJoin(\DB::raw("(SELECT * FROM message_thread_participants WHERE user_id = {$participant}) AS participant{$key}"), "participant{$key}.thread_id", '=', 'message_threads.id');
            $query->whereNotNull("participant{$key}.user_id");
        }
        // $query->where('message_threads.participant', 'like', '%'.$participant.'%');

        if (count($participants) <= 2) {
            $threadId = implode('_', $participants);
            $query->where('message_threads.id', '!=', $threadId);
        }
    }
}
