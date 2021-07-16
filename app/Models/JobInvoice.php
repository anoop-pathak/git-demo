<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Request;
use App\Services\Grid\SortableTrait;
use App\Services\QuickBooks\SynchEntityInterface;
use App\Services\QuickBooks\QboSynchableTrait;
use App\Services\QuickBookDesktop\Traits\QbdSynchableTrait;

class JobInvoice extends BaseModel implements SynchEntityInterface
{
    use SortableTrait;
    use SoftDeletes;
    use QboSynchableTrait;
    use QbdSynchableTrait;

    const OPEN = 'open';
    const CLOSED = 'closed';
    const JOB = 'job';
    const CHANGE_ORDER = 'change_order';
    const PROPOSAL = 'proposal';
    const QUICKBOOK_INVOICE_PREFIX = 'JP-';
    const DEFAULT_INVOICE_NAME = 'INVOICE';

    //Make it available in the json response
    protected $appends = ['open_balance', 'total_amount'];

    protected $fillable = [
        'customer_id',
        'job_id',
        'title',
        'amount',
        'detail',
        'file_path',
        'quickbook_invoice_id',
        'status',
        'tax_rate',
        'description',
        'date',
        'due_date',
        'proposal_id',
        'file_size',
        'quickbook_sync',
        'signature',
        'signature_date',
        'custom_tax_id',
        'taxable',
        'type',
        'invoice_number',
        'order',
        'note',
        'name',
        'unit_number',
        'division_id',
        'branch_code',
        'ship_to_sequence_number',
        'qb_file_path',
        'qb_file_size',
        'quickbook_sync_token',
        'quickbook_sync_status',
        'last_updated_origin',
        'origin',
        'total_amount',
        'qb_division_id',
        'taxable_amount',
        'qb_desktop_txn_id',
        'qb_desktop_sequence_number'
    ];

    protected $updateDescriptionRules = [
        'invoice_id' => 'required|exists:job_invoices,id',
        'description' => 'required|max:4000',
    ];

    protected $updateRules = [
        'due_date' => 'date',
        'date' => 'date',
        'note' => 'max:2000',
    ];

    protected $rules = [
        'job_id' => 'required',
        'due_date' => 'date',
        'date' => 'date',
        'note' => 'max:2000',

    ];

    protected $deleteJobInvoiceRule = [
        'invoice_id' => 'required',
        'password' => 'required',
    ];

    public function getQBOId(){
        return $this->quickbook_invoice_id;
    }

    public function getQBDId()
    {
        return $this->qb_desktop_txn_id;
    }

     /**
     * Get Quickbook Log Display Name
     * @return Display Name
     */
    public function getLogDisplayName()
    {
        return  self::QUICKBOOK_INVOICE_PREFIX.$this->invoice_number;
    }

    protected function getUpdateDescriptionRules()
    {
        return $this->updateDescriptionRules;
    }

    protected function getUpdateRules()
    {
        $input = Request::all();
        $rules = $this->updateRules;

        if (ine($input, 'lines')) {
            foreach ($input['lines'] as $key => $value) {
                $rules['lines.' . $key . '.description'] = 'required';
                $rules['lines.' . $key . '.amount'] = 'required|numeric|ten_digit_allow';
                $rules['lines.' . $key . '.trade_id'] = 'required_with:lines.' . $key . '.work_type_id';
            }
        } else {
            $rules['lines.0.description'] = 'required';
            $rules['lines.0.amount'] = 'required|numeric|ten_digit_allow';
            $rules['lines.0.trade_id'] = 'required_with:lines.0.work_type_id';
        }

        return $rules;
    }

    protected function getRules()
    {
        $input = Request::all();
        $rules = $this->rules;

        if (ine($input, 'lines')) {
            foreach ($input['lines'] as $key => $value) {
                $rules['lines.' . $key . '.description'] = 'required';
                $rules['lines.' . $key . '.amount'] = 'required|numeric|ten_digit_allow';
                $rules['lines.' . $key . '.trade_id'] = 'required_with:lines.' . $key . '.work_type_id';
            }
        } else {
            $rules['lines.0.description'] = 'required';
            $rules['lines.0.amount'] = 'required|numeric|ten_digit_allow';
            $rules['lines.0.trade_id'] = 'required_with:lines.0.work_type_id';
        }

        return $rules;
    }

    protected function getDetailAttribute($value)
    {
        return json_decode($value);
    }

    protected function setDetailAttribute($value)
    {
        if (!is_null($value)) {
            $this->attributes['detail'] = json_encode((array)$value);
        }
    }

    protected function getDeleteJobInvoiceRule()
    {
        return $this->deleteJobInvoiceRule;
    }

    /**
     *
     * @return [int] [open balance of invoice]
     */
    public function getOpenBalanceAttribute()
    {
        $openBalance = $this->total_amount - $this->payments->sum('amount');

        return number_format($openBalance, 2, '.', '');
    }

    /**
     * custom attribute of return total_amount
     * @return total amount
     */
    public function getTotalAmountAttribute($value)
    {

        return numberFormat($value);
    }

    public function getAmountAttribute($value)
    {

        return number_format($value, 2, '.', '');
    }

    public function getTaxRate()
    {
        if ($this->taxable && $this->tax_rate) {
            return $this->tax_rate;
        }

        return 0;
    }

    public function jobPayments()
    {
        return $this->belongsToMany(JobPayment::class, 'invoice_payments', 'invoice_id', 'payment_id');
    }

    /**
     * relationship
     * @return [type] [description]
     */
    public function payments()
    {
        return $this->hasMany(InvoicePayment::class, 'invoice_id');
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }


    public function setDateAttribute($value)
    {
        if (!isValidDate($value)) {
            $value = null;
        }

        return $this->attributes['date'] = $value;
    }


    public function setDueDateAttribute($value)
    {
        if (!isValidDate($value)) {
            $value = null;
        }

        return $this->attributes['due_date'] = $value;
    }

    public function getDateAttribute($value)
    {
        if (!$value) {
            return null;
        }

        return Carbon::parse($value)->format('Y-m-d');
    }

    public function getDueDateAttribute($value)
    {
        if (!$value) {
            return null;
        }

        return Carbon::parse($value)->format('Y-m-d');
    }

    public function getCreatedDate()
    {
        $dateTime = new Carbon($this->created_at);

        return $dateTime->format('Y-m-d');
    }

    public function isJobInvoice()
    {
        return ($this->type == self::JOB);
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }

    public function lines()
    {
        return $this->hasMany(JobInvoiceLine::class, 'invoice_id');
    }

    public function customTax()
    {
        return $this->belongsTo(CustomTax::class);
    }

    public function changeOrder()
    {
        return $this->hasOne(ChangeOrder::class, 'invoice_id', 'id')->select('id', 'invoice_id');
    }

    public function division()
    {
        return $this->belongsTo(Division::class);
    }
    
    public function branch()
    {
        return $this->belongsTo(SupplierBranch::class, 'branch_code', 'branch_code')->where('supplier_branches.company_id', getScopeId());
    }
    
    public function srsShipToAddresses()
    {
        return $this->belongsTo(SrsShipToAddress::class, 'ship_to_sequence_number', 'ship_to_sequence_id')
            ->where('company_id', getScopeId());
    }
    
    public function getIsOpenAttribute()
    {
        return $this->status == static::OPEN;
    }
    
    public function getIsClosedAttribute()
    {
        return $this->status == static::CLOSED;
    }

    public function getSignedUrlAttribute()
    {
        if(!$path = $this->file_path) return;

        return \FlySystem::getAwss3SignedUrl(\Config::get('jp.BASE_PATH').$path);
    }
}
