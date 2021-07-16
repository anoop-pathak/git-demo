<?php
namespace App\Models;

class QBDPayment extends BaseModel
{
    protected $table = 'qbd_payments';

    /***** Scopes End *****/

    public function getMetaAttribute($value)
    {
        return json_decode($value, true);
    }
}