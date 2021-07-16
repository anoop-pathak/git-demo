<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeletedInvoicePayment extends Model {

	protected $table = 'deleted_invoice_payments';

	protected $appends = [];

	protected $fillable = ['customer_id', 'data', 'created_by', 'job_id', 'company_id'];

}