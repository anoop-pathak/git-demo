<?php

namespace App\Models;

use Carbon\Carbon;

class Notification extends BaseModel
{

    protected $fillable = ['user_id', 'subject', 'body', 'object_id', 'object_type', 'sent_at', 'sender_id'];

    private $relatedObject = null;

    public function recipients()
    {
        return $this->belongsToMany(User::class, 'notification_recipient', 'notification_id', 'user_id');
    }

    public function subject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function from($user)
    {
        return $this->sender()->associate($user);
    }

    public function regarding($object)
    {
        if (is_object($object)) {
            $this->object_id = $object->id;
            $this->object_type = (new \ReflectionClass($object))->getShortName();//get_class($object);
        }

        return $this;
    }

    public function deliver()
    {
        $this->sent_at = new Carbon;
        $this->save();

        return $this;
    }

    public function hasValidObject()
    {
        try {
            $type = $this->object_type;

            if ($type == 'Task') {
                $type = Task::class;
            }

            $object = call_user_func_array($type . '::whereId', [$this->object_id]);

            // send deleted customer and jobs also..
            if (in_array($type, ['Customer', 'Job'])) {
                $object->withTrashed();
            }

            $object = $object->firstOrFail();
        } catch (\Exception $e) {
            return false;
        }
        $this->relatedObject = $object;
        return true;
    }

    public function getObject()
    {
        if (!$this->relatedObject) {
            $hasObject = $this->hasValidObject();

            if (!$hasObject) {
                throw new \Exception(sprintf("No valid object (%s with ID %s) associated with this notification.", $this->object_type, $this->object_id));
            }
        }
        return $this->relatedObject;
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function setBodyAttribute($value)
    {
        if (is_array($value)) {
            $value = json_encode($value);
        }
        $this->attributes['body'] = $value;
    }

    public function getBodyAttribute($body)
    {
        if (is_JSON($body)) {
            return json_decode($body);
        }
        return $body;
    }
}
