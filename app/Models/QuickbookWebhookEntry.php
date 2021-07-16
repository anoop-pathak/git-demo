<?php
namespace App\Models;

class QuickbookWebhookEntry extends BaseModel {

    protected $fillable = [
        'quickbook_webhook_id', 'realm_id',
        'object_id', 'object_type',
        'object_type', 'operation',
        'object_updated_at', 'meta',
        'company_id', 'status', 'extra', 'msg'
    ];

    const STATUS_PENDING = 'Pending';
    const STATUS_ABANDONED = "Abandoned";
    const STATUS_ERROR = "Error";
    const STATUS_PROCESSED = 'Processed';
    const STATUS_Success = 'Success';

    public function setExtraAttribute($value)
    {
        if(is_array($value)) {

        	$this->attributes['extra'] = json_encode($value);
		} else {

			$this->attributes['extra'] = $value;
		}
    }

    public function getExtraAttribute($value)
    {
		if(is_array(json_decode($value,true))) {

			return json_decode($value,true);
		} else {

			return $value;
		}
    }

    public function webhook()
    {
        return $this->belongsTo(QuickbookWebhooks::class, 'quickbook_webhook_id', 'id');
    }

    public function abandon($msg){
        $this->msg = $msg;
        $this->status = self::STATUS_ABANDONED;
        return $this->save();
    }

    public function markFailed(){
        $this->status = self::STATUS_ERROR;
        return $this->save();
    }
}