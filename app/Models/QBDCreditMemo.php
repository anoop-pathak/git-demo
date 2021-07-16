<?php
namespace App\Models;

class QBDCreditMemo extends BaseModel
{
    protected $table = 'qbd_credit_memo';

    /***** Scopes End *****/

    public function getMetaAttribute($value)
    {
        return json_decode($value, true);
    }
}