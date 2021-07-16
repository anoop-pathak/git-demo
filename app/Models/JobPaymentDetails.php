<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobPaymentDetails extends Model
{

    public $table = 'job_payment_details';

    protected $fillable = ['payment_id', 'description', 'amount', 'quantity'];

    public function getTotalAmount()
    {
        return $this->amount * $this->quantity;
    }
}
