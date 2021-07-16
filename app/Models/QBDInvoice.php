<?php
namespace App\Models;

class QBDInvoice extends BaseModel
{

    protected $fillable = ['company_id', 'qb_desktop_txn_id', 'customer_ref', 'txn_date', 'txn_number', 'edit_sequence', 'ref_number', 'due_date', 'qb_creation_date', 'qb_modified_date', 'item_sales_tax_ref', 'sales_tax_percentage', 'sales_tax_total', 'applied_amount', 'sub_total', 'balance_remaining', 'memo', 'meta'];

    protected $table = 'qbd_invoices';

    /***** Scopes End *****/

    public function getMetaAttribute($value)
    {
        return json_decode($value, true);
    }
}