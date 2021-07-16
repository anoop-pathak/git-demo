<?php

namespace App\Services\Invoices;

use App\Models\Company;
use App\Services\Contexts\Context;
use App\Services\Recurly\Recurly;

class InvoicesService
{

    protected $recurly;
    protected $scope;

    function __construct(Recurly $recurly, Context $scope)
    {
        $this->recurly = $recurly;
        $this->scope = $scope;
    }

    public function getInvoices()
    {
        $companyId = $this->scope->id();
        $company = Company::find($companyId);
        return $this->recurly->getInvoices($company->recurly_account_code);
    }

    public function getPdf($invoiceNumber)
    {
        return $this->recurly->getPDF($invoiceNumber);
    }
}
