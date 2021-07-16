<?php
namespace App\Models;

class QBOCustomer extends BaseModel
{

    public $primaryKey = 'qb_id';
    protected $fillable = ['company_id', 'qb_id', 'first_name', 'last_name', 'display_name', 'company_name', 'email', 'is_sub_customer', 'qb_parent_id', 'primary_phone_number', 'mobile_number', 'alter_phone_number', 'meta', 'created_at', 'updated_at', 'qb_creation_date', 'qb_modified_date', 'level', 'total_credit_count', 'total_invoice_count', 'total_payment_count', 'address_meta'];

    protected $table = 'qbo_customers';

    protected function getQBFinancialRules()
    {
        $rules = [
            'qb_customer_id' => 'required'
        ];

        return $rules;
    }

    /***** Relations Start *****/

    public function customers()
    {
        return $this->belongsToMany(Customer::class, 'quickbook_sync_customers', 'customer_id', 'qb_id');
    }

    public function qbJobs()
    {
        return $this->hasMany(QBOCustomer::class, 'qb_parent_id', 'qb_id')
            ->where('qbo_customers.company_id', getScopeId())
            ->where('qbo_customers.level', 1);
    }

    public function qbBills()
    {
        return $this->hasMany(QBOBill::class, 'qb_customer_id', 'qb_id')
            ->where('qbo_bills.company_id', getScopeId());
    }

    /***** Relations End *****/

    /***** Scopes Start *****/

    public function scopeExcludeMappedCustomers($query, $isQBD = false)
    {
        $matchingColumn = 'quickbook_id';
        if($isQBD) {
            $matchingColumn = 'qb_desktop_id';
        }

        $query->leftJoin('customers', function($query) use ($matchingColumn) {
            $query->on("customers.$matchingColumn", '=', 'qbo_customers.qb_id');
            $query->where('customers.company_id', '=', getScopeId());
        });

        $query->whereNull("customers.$matchingColumn");
    }

    public function scopeExcludeSubCustomer($query)
    {
        return $query->whereNull('qb_parent_id');
    }

    public function scopeValid($query)
    {
        $query->whereNotNull('qbo_customers.first_name')
            ->whereNotNull('qbo_customers.last_name')
            ->where(function($query) {
                $query->whereNotNull('qbo_customers.primary_phone_number')
                    ->orWhereNotNull('qbo_customers.alter_phone_number')
                    ->orWhereNotNull('qbo_customers.mobile_number');
            });
    }

    public function scopePhoneNumbers($query, $phones = []) {
        $query->where(function($query) use ($phones){
            $query->whereIn('primary_phone_number', $phones)
                ->orWhereIn('alter_phone_number', $phones)
                ->orWhereIn('qbo_customers.mobile_number', $phones);
        });
    }

    public function scopeExcludeBatch($query, $batchId)
    {
        $query->leftJoin('quickbook_sync_customers', function($query) use($batchId){
            $query->on('quickbook_sync_customers.qb_id', '=', 'qbo_customers.qb_id');
            $query->where('quickbook_sync_customers.batch_id', '=', $batchId);
        });
        $query->whereNull('quickbook_sync_customers.batch_id');
    }

    /**
     * Find Matching Customer
     * @param  array  $phones      array of phones
     * @param  string $email       email
     * @param  string $fullName    fullName (first_name, Last_name)
     * @param  string $companyName companyName
     * @param  int    $batchId     batch Id
     * @return QBOCustomer
     */
    protected function findMatchingCustomer($phones, $email = null, $fullName = null, $companyName = null, $batchId = null, $isQBD = false)
    {
        

        $qboCustomer = QBOCustomer::on('mysql2')
            ->where('qbo_customers.company_id', getScopeId())
            ->excludeSubCustomer()
            ->orderBy('qb_modified_date', 'desc')
            ->excludeMappedCustomers($isQBD)
            ->select('qbo_customers.*');
        if($batchId) {
            $qboCustomer->excludeBatch($batchId);
        }

        $qboCustomerExact = clone $qboCustomer;
        $qboCustomerPhone = clone $qboCustomer;
        $qboCustomerEmail = clone $qboCustomer;

        if($companyName) {
            $qboCustomer->where(function($query) use($companyName) {
                $query->where('qbo_customers.company_name', $companyName)
                      ->orWhere('qbo_customers.display_name', $companyName);
            });

            return $qboCustomer->first();
        }

        if($email) {
            $qboCustomerExact->where('qbo_customers.email', $email);
        } else {
            $qboCustomerExact->where(\DB::raw('CONCAT_WS(" ", qbo_customers.first_name, qbo_customers.last_name)'), $fullName);
        }

        $qboCustomerExact->phoneNumbers($phones);
        $customer = $qboCustomerExact->first();
        if(!$customer) {
            if($email) {
                $qboCustomerPhone->where(\DB::raw('CONCAT_WS(" ", qbo_customers.first_name, qbo_customers.last_name)'), $fullName);
                $qboCustomerPhone->phoneNumbers($phones);
                $customer = $qboCustomerPhone->first();
            }
            if(!$customer && $email && $fullName) {
                $qboCustomerEmail->where('qbo_customers.email', $email);
                $qboCustomerEmail->where(\DB::raw('CONCAT_WS(" ", qbo_customers.first_name, qbo_customers.last_name)'), $fullName);
                $customer = $qboCustomerEmail->first();
            }
        }
        return $customer;
    }

    /***** Scopes End *****/

    public function getMetaAttribute($value)
    {
        return json_decode($value, true);
    }

    public function getAddressMetaAttribute($value)
    {
        return json_decode($value, true);
    }
}