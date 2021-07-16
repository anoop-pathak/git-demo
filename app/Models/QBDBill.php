<?php
namespace App\Models;

class QBDBill extends BaseModel
{
    protected $table = 'qbd_bills';

    /***** Scopes End *****/

    public function getMetaAttribute($value)
    {
        return json_decode($value, true);
    }
}