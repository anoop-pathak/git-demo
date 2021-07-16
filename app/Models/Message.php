<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\Grid\DivisionTrait;
use Request;

class Message extends Model
{

    use SoftDeletes;
    use DivisionTrait;

    /*
	 * Constants for message status..
	 */
    const UNREAD = 1;
    const READ = 2;
    const DELETED = 3;

    const SMS_STATUS_RECEIVED	= 'RECEIVED';
	const SMS_STATUS_SENT		= 'SENT';
	const SMS_STATUS_QUEUED		= 'QUEUED';
	const SMS_STATUS_FAILED		= 'FAILED';
	const SMS_STATUS_DELIVERED 	= 'DELIVERED';

    protected $fillable = ['company_id', 'sender_id', 'subject', 'content', 'thread_id', 'sms_status', 'sms_id', 'customer_id'];

    protected $table = 'messages';

    protected $rules = [
        'participants' => 'required',
        'tag_ids'      =>  'array',
        'content' => 'required',
    ];

    protected $smsRules = [
		'phone_number' => 'required',
		'message'      => 'required|max:160',
	];

	protected function getSMSRules()
	{
		$smsRules = $this->smsRules;
		if (!is_null(Request::get('media'))) {
			$rules['media'] = 'required|array|between:0,1';
			foreach ((array)Request::get('media') as $key => $value) {
		        $rules['media.' . (int)$key . '.type'] = 'required|in:upload,proposal,estimate,material_list,workorder,invoice,worksheet,credit,measurement,resource';
		        $rules['media.' . (int)$key . '.value'] = 'required';
			}
		}

		return $smsRules;
	}

    protected function getRules()
    {
        $rules = $this->rules;

        if(Request::get('tag_ids') || Request::get('thread_id')) {
			unset($rules['participants']);
        }
        if(!empty(Request::get('participant_setting'))) {
			$input = Request::all();

			if(ine($input,'participant_setting')) {
                foreach ((array)$input['participant_setting'] as $key => $value) {
					$rules['participant_setting.' .$key] = ["in:customer_rep,subs,estimators,company_crew"];
				}
			}
			unset($rules['participants']);
		}
		return $rules;
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id')->select('id', 'first_name', 'last_name');
    }

    public function customer()
	{
		return $this->belongsTo(Customer::class, 'customer_id');
	}

    public function participants()
    {
        return $this->belongsToMany(User::class, 'message_thread_participants', 'thread_id', 'user_id');
    }

    public function status()
    {
        return $this->belongsToMany(User::class, 'message_status', 'message_id', 'user_id');
    }

    public function thread()
    {
        return $this->belongsTo(MessageThread::class);
    }

    public function task()
	{
		return $this->hasOne(Task::class, 'message_id');
	}

	public function job() {
		return $this->belongsTo(Job::class)->withTrashed();
	}

	public function media(){
		return $this->hasMany(PhoneMessageMedia::class, 'sid', 'sms_id');
	}

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function setSubjectAttribute($value)
    {
        return $this->attributes['subject'] = ($value) ? $value : '';
    }

    /**
	 * Get Constant Value of SMS status
	 * @param $status
	 * @return string
	 */
	protected function getSMSStatus($status)
	{
		switch ($status) {
			case 'sent':
				return Message::SMS_STATUS_SENT;
				break;

			case 'received':
				return Message::SMS_STATUS_RECEIVED;
				break;

			case 'delivered':
				return Message::SMS_STATUS_DELIVERED;
				break;


			case 'queued':
				return Message::SMS_STATUS_QUEUED;
				break;

			case 'failed':
				return Message::SMS_STATUS_FAILED;
				break;

			default:
				return $status;
				break;
		}
	}

	public function PhoneMessage()
	{
		return $this->hasOne(PhoneMessage::class);
	}
}
