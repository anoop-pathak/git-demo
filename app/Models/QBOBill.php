<?php
namespace App\Models;

class QBOBill extends BaseModel
{

    protected $fillable = ['company_id', 'qb_customer_id', 'qb_vendor_id', ' due_date', 'total_amount', 'qb_creation_date', 'qb_modified_date', 'meta', 'qb_id'];

    protected $table = 'qbo_bills';

    /***** Relations Start *****/

    public function qboCustomers()
    {
        return $this->belongsTo(QBOCustomer::class, 'qb_customer_id', 'qb_id');
    }

    /***** Scopes End *****/

    public function getMetaAttribute($value)
    {
        return json_decode($value, true);
    }
}