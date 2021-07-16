<?php
namespace App\Models;

use App\Services\Grid\SortableTrait;
use Carbon\Carbon;
use App\Models\BaseModel;

class VendorBillLine extends BaseModel
{
    use SortableTrait;

    protected $fillable = ['vendor_bill_id', 'financial_account_id', 'rate', 'quantity', 'description'];

    public function getCreatedAtAttribute($value) {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function getUpdatedAtAttribute($value) {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function Vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function VendorBill()
    {
        return $this->belongsTo(VendorBill::class);
    }

    public function financialAccount()
    {
        return $this->belongsTo(FinancialAccount::class)->withTrashed();
    }
}
