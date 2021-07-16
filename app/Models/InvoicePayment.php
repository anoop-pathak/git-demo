<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoicePayment extends Model
{

    protected $table = 'invoice_payments';

    protected $appends = ['quickbook_invoice_id'];

   protected $fillable = ['invoice_id', 'payment_id', 'amount', 'created_at', 'ref_id', 'job_id', 'credit_id'];

    public function getQuickbookInvoiceIdAttribute()
    {

        return isset($this->jobInvoice->quickbook_invoice_id) ? $this->jobInvoice->quickbook_invoice_id : null;
    }

    public function jobInvoice()
    {
        return $this->belongsTo(JobInvoice::class, 'invoice_id', 'id');
    }

    public function jobPayment()
    {
        return $this->belongsTo(JobPayment::class, 'payment_id');
    }

    public function refJobPayment()
    {
        return $this->belongsTo(JobPayment::class, 'ref_id');
    }
    public function jobCredit()
    {
        return $this->belongsTo(JobCredit::class, 'credit_id');
    }
}
