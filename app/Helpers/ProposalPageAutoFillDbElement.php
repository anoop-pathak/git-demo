<?php

namespace App\Helpers;

use App\Models\Template;
use Carbon\Carbon;
use DOMDocument;
use Exception;
use Illuminate\Support\Facades\Lang;
use Settings;
use Config;

class ProposalPageAutoFillDbElement
{

    protected $customerFirstName;
    protected $customerLastName;
    protected $customerFullName;
    protected $customerEmail;
    protected $customerReferral;
    protected $customerNote;
    protected $customerSecName;
    protected $customerCommercialName;
    protected $customerSecAndFullName;
    protected $customerFirstPhoneNumber;
    protected $customerRepName;
    protected $customerRepEmail;
    protected $customerRepPhone;
    protected $customerFullAddressOneLine;
    protected $customerAddress;
    protected $customerAddressLine1;
    protected $customerAddressZip;
    protected $customerAddressCity;
    protected $customerStateName;
    protected $customerCountryName;
    protected $customerBillingAddressOneLine;
    protected $customerBillingAddress;
    protected $customerBillingAddressLine;
    protected $customerBillingZip;
    protected $customerBillingCity;
    protected $customerBillingStateName;
    protected $customerBillingCountryName;
    protected $customerCompanyNameResidential;
    protected $jobNumber;
    protected $jobName;
    protected $jobAltId;
    protected $jobTaxRate;
    protected $jobTaxAmount;
    protected $jobAmoumt;
    protected $jobTotalAmount;
    protected $jobTrades;
    protected $jobWorkTypes;
    protected $jobEstimators;
    protected $jobReps;
    protected $jobSubNames;
    protected $jobDescription;
    protected $jobContractSignedDate;
    protected $changeOrderAmount;
    protected $jobFullAddressOneLine;
    protected $jobAddress;
    protected $jobAddressLine2;
    protected $jobAddressZip;
    protected $jobAddressCity;
    protected $jobAddressState;
    protected $JobCountryName;
    protected $jobContactFirstName;
    protected $jobContactLastName;
    protected $jobContactFullName;
    protected $jobContactEmail;
    protected $jobContactFullAddress;
    protected $jobContactAddress;
    protected $jobContactAddressLine2;
    protected $jobContactCity;
    protected $jobContactZip;
    protected $jobContactState;
    protected $jobContactCountry;
    protected $jobContactHomeNumber;
    protected $jobContactCellNumber;
    protected $jobContactPhoneNumber;
    protected $jobContactOfficeNumber;
    protected $jobContactOtherNumber;
    protected $jobContactFaxNumber;
    protected $jobContactNumber;
    protected $hasAttributes;
    protected $currrentDate;
    protected $jobLeadNumber;
    protected $jobCompletionDate;
    protected $jobCustomerWebPage;

    protected $jobInsuranceCompanyName;
    protected $jobInsuranceNumber;
    protected $jobInsuranceFax;
    protected $jobInsuranceEmail;
    protected $jobInsurancePhone;
    protected $jobInsuranceAdjusterName;
    protected $jobInsuranceAdjusterPhone;
    protected $jobInsuranceAdjusterEmail;
    protected $jobInsuranceRCV;
    protected $jobInsuranceNetClaim;
    protected $jobInsuranceSupplement;
    protected $jobInsuranceDepreciation;
    protected $jobInsuranceDeductableAmount;
    protected $jobInsurancePolicyNumber;
    protected $jobInsuranceContingencyContractSignedDate;
	protected $jobInsuranceDateOfLoss;
    protected $jobInsuranceAcv;
    protected $jobInsuranceTotal;
    protected $jobPurchaseOrderNumber;
	protected $jobMaterialDeliveryDate;
	protected $jobEstimatorsFirstName;
	protected $jobEstimatorsLastName;
	protected $jobEstimatorsEmail;
	protected $jobEstimatorsPhone;
	protected $jobSubFirstNames;
	protected $jobSubLastNames;
	protected $jobRepNumber;
	protected $jobInsuranceUpgrades;
	protected $jobScheduleStartDate;
	protected $jobScheduleEndDate;
	protected $jobAppointmentStartDate;
	protected $jobAppointmentEndDate;

    protected $companyLicenseNumber1;
	protected $companyLicenseNumber2;
	protected $companyLicenseNumber3;
	protected $companyLicenseNumbersAll;
	protected $companyLicenseNumbers;
    protected $companyName;

	protected $companyEmail;
	protected $companyAdditionalEmail;
	protected $companyPhone;
	protected $companyAdditionalPhone;
	protected $companyFullAddress;
	protected $companyWebsiteUrl;

    public function setAttributes($job, $serialNumber)
    {
        $this->serialNumber = $serialNumber;
        $customer = $job->customer;
        $company = $job->company;

        $countryCode = $company->country->code;
        $financialCalculation = $job->financialCalculation;
        $companyLicenseNumbers = $company->licenseNumbers->pluck('license_number', 'position')->toArray();

        $this->customerFirstName = $customer->first_name;
        $this->customerLastName = $customer->last_name;
        $this->customerFullName = $customer->full_name;
        $this->customerEmail = $customer->email;
        $this->customerCompanyNameResidential = $customer->company_name;
        $this->companyLicenseNumber1 = issetRetrun($companyLicenseNumbers, 1) ?: "";
		$this->companyLicenseNumber2 = issetRetrun($companyLicenseNumbers, 2) ?: "";
		$this->companyLicenseNumber3 = issetRetrun($companyLicenseNumbers, 3) ?: "";
		$this->companyLicenseNumbersAll = implode(", ", $companyLicenseNumbers);
        $this->companyLicenseNumber = $company->license_number;
        $this->companyEmail = $company->email;
		$this->companyAdditionalEmail = $company->present()->companyAdditionalEmails;
		$this->companyPhone = $company->office_phone;
		$this->companyAdditionalPhone = $company->present()->companyAdditionalPhone;
		$this->companyFullAddress = $company->present()->fullAddress;
		$this->companyWebsiteUrl = Settings::get('WEBSITE_LINK');
        $this->companyName = $company->name;
        $referredBy = $customer->referredBy();
        $customerReferral = null;

        if ($referredBy) {
            if ($customer->referred_by_type == 'customer') {
                $this->customerReferral = $referredBy->full_name;
            } elseif ($customer->referred_by_type == 'referral') {
                $this->customerReferral = $referredBy->name;
            } elseif ($customer->referred_by_type == 'other') {
                $this->customerReferral = $customer->referred_by_note;
            }
        }

        $this->customerNote = $customer->note;
        $this->customerSecName = $customer->present()->secondaryFullName;
        $this->customerCommercialName = $customer->present()->customerCommercialName;
		$this->customerSecAndFullName = $customer->is_commercial ? $customer->full_name : null;
        $this->customerFirstPhoneNumber = phoneNumberFormat($customer->firstPhone->number, $countryCode);
        $this->jobCustomerWebPage = config('app.url').'customer_job_preview/'.$job->share_token;

        if ($rep = $customer->rep) {
            $repProfile = $rep->profile;
            $this->customerRepName = $rep->full_name;
            $this->customerRepEmail = $rep->email;
            $this->customerRepPhone = $repProfile->phone;
            if (!$this->customerRepPhone
                && !empty($phones = $repProfile->additional_phone)) {
                $repPhone = reset($phones);
                $this->customerRepPhone = $repPhone->phone;
            }
        }

        if ($customerAddress = $customer->address) {
            $this->customerFullAddressOneLine = $customerAddress->present()->fullAddressOneLine;
            $this->customerAddress = $customerAddress->address;
            $this->customerAddressLine1 = $customerAddress->address_line_1;
            $this->customerAddressLine2 = $customerAddress->address_line_2;
            $this->customerAddressCity = $customerAddress->city;
            $this->customerAddressZip = $customerAddress->zip;
            $this->customerStateName = $customerAddress->present()->stateName;
            $this->customerCountryName = $customerAddress->present()->countryName;
        }

        if ($billingAddress = $customer->billing) {
            $this->customerBillingAddressOneLine = $billingAddress->present()->fullAddressOneLine;
            $this->customerBillingAddress = $billingAddress->address;
            $this->customerBillingAddressLine = $billingAddress->address_line_1;
            $this->customerBillingZip = $billingAddress->zip;
            $this->customerBillingCity = $billingAddress->city;
            $this->customerBillingStateName = $billingAddress->present()->stateName;
            $this->customerBillingCountryName = $billingAddress->present()->countryName;
        }

        $this->jobNumber = $job->number;
        $this->jobName   = $job->name;
        $this->jobAltId = $job->alt_id;
        $taxRate = ($job->tax_rate) ? $job->tax_rate . '%' : null;
        $this->jobTaxRate = $taxRate;
        $this->jobTaxAmount = showAmount(calculateTax($job->amount, $job->tax_rate));
        $this->jobAmoumt = showAmount(($job->amount) ? $job->amount : 0);
        $this->jobTotalAmount = showAmount($job->getTotalAmount());
        $this->jobEstimators = $job->present()->jobEstimators;
        $this->jobTrades = $job->present()->jobTrades;
        $this->jobWorkTypes = $job->present()->jobWorkTypes;
        $this->jobReps = $job->present()->jobReps;
        $this->jobLaboursName = $job->present()->jobLaboursName;
        $this->jobSubNames = $job->present()->jobSubNames;
        $this->jobSubFirstNames = $job->present()->jobSubFirstNames;
		$this->jobSubLastNames  = $job->present()->jobSubLastNames;
        $this->changeOrderAmount = showAmount($financialCalculation->total_change_order_amount);
        $this->jobDescription = $job->description;
        $this->jobLeadNumber = $job->lead_number;
        $this->jobCompletionDate = $job->completion_date;
        $this->jobPurchaseOrderNumber = $job->purchase_order_number;
		$this->jobMaterialDeliveryDate = $job->material_delivery_date;
		$this->jobEstimatorsFirstName = $job->present()->jobEstimatorsFirstName;
		$this->jobEstimatorsLastName = $job->present()->jobEstimatorsLastName;
		$this->jobEstimatorsEmail = $job->present()->jobEstimatorsEmail;
		$this->jobEstimatorsPhone = $job->present()->jobEstimatorsAndRepPhone($job->estimators, $countryCode);
		$this->jobRepNumber  = $job->present()->jobEstimatorsAndRepPhone($job->reps, $countryCode);
		$this->jobScheduleStartDate = $job->upcomingSchedules->first() ? $job->upcomingSchedules->first()->start_date_time : null;
		$this->jobScheduleEndDate = $job->upcomingSchedules->first() ? $job->upcomingSchedules->first()->end_date_time : null;
		$this->jobAppointmentStartDate = $job->upcomingAppointments->first() ? $job->upcomingAppointments->first()->start_date_time : null;
		$this->jobAppointmentEndDate = $job->upcomingAppointments->first() ? $job->upcomingAppointments->first()->end_date_time : null;

        if ($job->cs_date) {
            $this->jobContractSignedDate = Carbon::parse($job->cs_date)->format('m/d/Y');
        }

        if ($jobAddress = $job->address) {
            $this->jobFullAddressOneLine = $jobAddress->present()->fullAddressOneLine;
            $this->jobAddress = $jobAddress->address;
            $this->jobAddressLine2 = $jobAddress->address_line_2;
            $this->jobAddressZip = $jobAddress->zip;
            $this->jobAddressCity = $jobAddress->city;
            $this->jobAddressState = $jobAddress->present()->stateName;
            $this->JobCountryName = $jobAddress->present()->countryName;
        }

        $phoneNumbers = [];

        if ($job->contact_same_as_customer) {
            $this->jobContactFirstName = $this->customerFirstName;
            $this->jobContactLastName = $this->customerLastName;
            $this->jobContactFullName = $this->customerFullName;
            $this->jobContactEmail = $this->customerEmail;
            $this->jobContactAddress = $this->customerAddress;
            $this->jobContactAddressLine1 = $this->customerAddressLine1;
            $this->jobContactCity = $this->customerAddressCity;
            $this->jobContactZip = $this->customerAddressZip;
            $this->jobContactState = $this->customerStateName;
            $this->jobContactCountry = $this->customerCountryName;
            $this->jobContactFullAddress = $this->customerFullAddressOneLine;
            $phoneNumbers = $customer->phones;
        }

        if ((!$job->contact_same_as_customer) && ($jobContact = $job->primaryContact())) {
            $this->jobContactFirstName = $jobContact->first_name;
            $this->jobContactLastName = $jobContact->last_name;
            $this->jobContactFullName = $jobContact->full_name;
            $this->jobContactEmail = $jobContact->primaryEmail();
            $this->jobContactAddress = $jobContact->address->address;
            $this->jobContactAddressLine2 = $this->customerAddressLine2;
            $this->jobContactCity = $jobContact->address->city;
            $this->jobContactZip = $jobContact->address->zip;
            $this->jobContactState = $jobContact->present()->jobContactState($jobContact->address);
            $this->jobContactCountry = $jobContact->present()->jobContactCountry($jobContact->address);
            $this->jobContactFullAddress = $jobContact->present()->jobContactFullAddress;
            $phoneNumbers = $jobContact->phones;
        }

        $jobContactNumbers = [];

        foreach ($phoneNumbers as $phone) {
            switch ($phone->label) {
                case 'home':
                    $this->jobContactHomeNumber = phoneNumberFormat($phone->number, $countryCode);
                    break;
                case 'cell':
                    $this->jobContactCellNumber = phoneNumberFormat($phone->number, $countryCode);
                    break;
                case 'phone':
                    $this->jobContactPhoneNumber = phoneNumberFormat($phone->number, $countryCode);
                    break;

                case 'office':
                    $this->jobContactOfficeNumber = phoneNumberFormat($phone->number, $countryCode);
                    break;

                case 'other':
                    $this->jobContactOtherNumber = phoneNumberFormat($phone->number, $countryCode);
                    break;
                case 'fax':
                    $this->jobContactFaxNumber = phoneNumberFormat($phone->number, $countryCode);
                    break;
            }

            $jobContactNumbers[] = phoneNumberFormat($phone->number, $countryCode) . ' - (' . substr(ucfirst($phone->label), 0, 1) . ')';
        }

        if ($jobInsurance = $job->insuranceDetails) {
            $this->jobInsuranceCompanyName = $jobInsurance->insurance_company;
            $this->jobInsuranceNumber = $jobInsurance->insurance_number;
            $this->jobInsuranceFax = $jobInsurance->fax;
            $this->jobInsuranceEmail = $jobInsurance->email;
            $this->jobInsurancePhone = phoneNumberFormat($jobInsurance->phone, $countryCode);
            $this->jobInsuranceAdjusterName = $jobInsurance->adjuster_name;
            $this->jobInsuranceAdjusterPhone = phoneNumberFormat($jobInsurance->adjuster_phone, $countryCode);
            $this->jobInsuranceAdjusterEmail = $jobInsurance->adjuster_email;
            $this->jobInsuranceRCV = $jobInsurance->rcv;
            $this->jobInsuranceDeductableAmount = $jobInsurance->deductable_amount;
            $this->jobInsurancePolicyNumber = $jobInsurance->policy_number;
            $this->jobInsuranceContingencyContractSignedDate = $jobInsurance->contingency_contract_signed_date;
			$this->jobInsuranceDateOfLoss = $jobInsurance->date_of_loss;
            $this->jobInsuranceAcv = $jobInsurance->acv;
            $this->jobInsuranceSupplement = $jobInsurance->supplement;
            $this->jobInsuranceNetClaim = $jobInsurance->net_claim;
            $this->jobInsuranceDepreciation = $jobInsurance->depreciation;
            $this->jobInsuranceTotal = $jobInsurance->total;
            $this->jobInsuranceUpgrades = $jobInsurance->upgrade;
        }

        $this->jobContactNumber = implode(', ', $jobContactNumbers);
        $this->currrentDate = Carbon::now(\Settings::get('TIME_ZONE'))->format('m/d/Y');
        $this->hasAttributes = true;

        return $this;
    }

    /**
     * Auto fill template
     * @param  String $content HTML content
     * @return Template
     */
    public function fillTemplate($content)
    {
        if (!$this->hasAttributes) {
            throw new Exception("Attributes not set.");
        }

        $dom = new DOMDocument();
        $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        foreach ($dom->getElementsByTagName('input') as $item) {
            $attribute = $item->getAttribute('filled-val');
            switch ($attribute) {
                case 'CUSTOMER_NAME':
                    $item->setAttribute('value', $this->customerFullName);
                    break;

                case 'CUSTOMER_NAME_COMMERCIAL':
                case 'CUSTOMER_FNAME':
                    $item->setAttribute('value', $this->customerFirstName);
                    break;

                case 'CUSTOMER_LNAME':
                    $item->setAttribute('value', $this->customerLastName);
                    break;

                case 'CUSTOMER_EMAIL':
                    $item->setAttribute('value', $this->customerEmail);
                    break;

                case 'CUSTOMER_REFERRED_BY':
                    $item->setAttribute('value', $this->customerReferral);
                    break;

                case 'CUSTOMER_SEC_NAME':
                    $item->setAttribute('value', $this->customerSecName);
                    break;

                case 'CUSTOMER_SEC_NAME_COMMERCIAL':
                    $item->setAttribute('value', $this->customerCommercialName);

                case 'CUSTOMER_SEC_AND_FULL_NAME':
                    $item->setAttribute('value', $this->customerSecAndFullName);
                    break;

                case 'CUSTOMER_PHONE':
                    $item->setAttribute('value', $this->customerFirstPhoneNumber);
                    break;

                case 'CUSTOMER_NOTE':
                    $item->setAttribute('value', $this->customerNote);
                    break;

                case 'CUSTOMER_REP':
                    $item->setAttribute('value', $this->customerRepName);
                    break;

                case 'CUSTOMER_REP_EMAIL':
                    $item->setAttribute('value', $this->customerRepEmail);
                    break;

                case 'CUSTOMER_REP_PHONE':
                    $item->setAttribute('value', $this->customerRepPhone);
                    break;

                case 'CUSTOMER_FADDRESS':
                    $item->setAttribute('value', $this->customerFullAddressOneLine);
                    break;

                case 'CUSTOMER_ADDRESS':
                    $item->setAttribute('value', $this->customerAddress);
                    break;

                case 'CUSTOMER_ADDRESS_LINE_2':
                    $item->setAttribute('value', $this->customerAddressLine1);
                    break;

                case 'CUSTOMER_ZIP':
                    $item->setAttribute('value', $this->customerAddressZip);
                    break;

                case 'CUSTOMER_CITY':
                    $item->setAttribute('value', $this->customerAddressCity);
                    break;

                case 'CUSTOMER_STATE':
                    $item->setAttribute('value', $this->customerStateName);
                    break;

                case 'CUSTOMER_COUNTRY':
                    $item->setAttribute('value', $this->customerCountryName);
                    break;

                case 'JOB_CUSTOMER_WEB_PAGE':
                    $item->setAttribute('value', $this->jobCustomerWebPage);
                    break;

                case 'BILLING_FADDRESS':
                    $item->setAttribute('value', $this->customerBillingAddressOneLine);
                    break;

                case 'BILLING_ADDRESS':
                    $item->setAttribute('value', $this->customerBillingAddress);
                    break;

                case 'BILLING_ADDRESS_LINE_2':
                    $item->setAttribute('value', $this->customerBillingAddressLine);
                    break;

                case 'BILLING_ZIP':
                    $item->setAttribute('value', $this->customerBillingZip);
                    break;

                case 'BILLING_CITY':
                    $item->setAttribute('value', $this->customerBillingCity);
                    break;

                case 'BILLING_STATE':
                    $item->setAttribute('value', $this->customerBillingStateName);
                    break;

                case 'BILLING_COUNTRY':
                    $item->setAttribute('value', $this->customerBillingCountryName);
                    break;

                case 'DATE':
                    $item->setAttribute('value', $this->currrentDate);
                    break;

                case 'JOB_NUMBER':
                    $item->setAttribute('value', $this->jobNumber);
                    break;

                case 'JOB_NAME':
                    $item->setAttribute('value', $this->jobName);
                    break;

                case 'JOB_LEAD_NUMBER':
                    $item->setAttribute('value', $this->jobLeadNumber);
                    break;

                case 'JOB_COMPLETION_DATE':
                    $item->setAttribute('value', $this->jobCompletionDate);
                    break;

                case 'JOB_ALT_ID':
                    $item->setAttribute('value', $this->jobAltId);
                    break;

                case 'JOB_AMOUNT':
                    $item->setAttribute('value', $this->jobTotalAmount);
                    break;

                case 'JOB_PRICE':
                    $item->setAttribute('value', $this->jobAmoumt);
                    break;

                case 'JOB_TAX':
                    $item->setAttribute('value', $this->jobTaxRate);
                    break;

                case 'JOB_TAX_AMOUNT':
                    $item->setAttribute('value', $this->jobTaxAmount);
                    break;

                case 'JOB_ESTIMATOR':
                case 'JOB_ESTIMATOR_FULL_NAME':
                    $item->setAttribute('value', $this->jobEstimators);
                    break;
                case 'JOB_ESTIMATOR_FIRST_NAME':
                    $item->setAttribute('value', $this->jobEstimatorsFirstName);
                    break;

                case 'JOB_ESTIMATOR_LAST_NAME':
                    $item->setAttribute('value', $this->jobEstimatorsLastName);
                    break;

                case 'JOB_DESCRIPTION':
                    $item->setAttribute('value', $this->jobDescription);
                    break;

                case 'JOB_TRADE':
                    $item->setAttribute('value', $this->jobTrades);
                    break;

                case 'JOB_WORK_TYPE':
                    $item->setAttribute('value', $this->jobWorkTypes);
                    break;

                case 'JOB_REP':
                case 'JOB_REP_FULL_NAME':
                    $item->setAttribute('value', $this->jobReps);
                    break;

                case 'JOB_LABORS':
                    $item->setAttribute('value', $this->jobLaboursName);
                    break;

                case 'JOB_REP_FIRST_NAME':
                    $item->setAttribute('value', $this->jobRepsFirstName);
                    break;

                case 'JOB_REP_LAST_NAME':
                    $item->setAttribute('value', $this->jobRepsLastName);
                    break;

                case 'JOB_REP_NUMBER':
                    $item->setAttribute('value', $this->jobRepNumber);
                    break;

                case 'JOB_SUBS':
                case 'JOB_LABORS':
                    $item->setAttribute('value', $this->jobSubNames);
                    break;

                case 'JOB_CHANGE_ORDER':
                    $item->setAttribute('value', $this->changeOrderAmount);
                    break;

                case 'JOB_FADDRESS':
                    $item->setAttribute('value', $this->jobFullAddressOneLine);
                    break;

                case 'JOB_ADDRESS':
                    $item->setAttribute('value', $this->jobAddress);
                    break;

                case 'JOB_ADDRESS_LINE_2':
                    $item->setAttribute('value', $this->jobAddressLine2);
                    break;

                case 'JOB_ZIP':
                    $item->setAttribute('value', $this->jobAddressZip);
                    break;

                case 'JOB_CITY':
                    $item->setAttribute('value', $this->jobAddressCity);
                    break;

                case 'JOB_STATE':
                    $item->setAttribute('value', $this->jobAddressState);
                    break;

                case 'JOB_COUNTRY':
                    $item->setAttribute('value', $this->JobCountryName);
                    break;

                case 'JOB_CON_PER_FN':
                    $item->setAttribute('value', $this->jobContactFirstName);
                    break;

                case 'JOB_CON_PER_LN':
                    $item->setAttribute('value', $this->jobContactLastName);
                    break;

                case 'JOB_CON_PER_FULL_NAME':
                    $item->setAttribute('value', $this->jobContactFullName);
                    break;

                case 'JOB_CON_PER_EMAIL':
                    $item->setAttribute('value', $this->jobContactEmail);
                    break;

                case 'JOB_CON_PER_FULL_ADDRESS':
                    $item->setAttribute('value', $this->jobContactFullAddress);
                    break;

                case 'JOB_CON_PER_ADDRESS':
                    $item->setAttribute('value', $this->jobContactAddress);
                    break;

                case 'JOB_CON_PER_ADDRESS_LINE_2':
                    $item->setAttribute('value', $this->jobContactAddressLine2);
                    break;

                case 'JOB_CON_PER_CITY':
                    $item->setAttribute('value', $this->jobContactCity);
                    break;

                case 'JOB_CON_PER_ZIP':
                    $item->setAttribute('value', $this->jobContactZip);
                    break;

                case 'JOB_CON_PER_STATE':
                    $item->setAttribute('value', $this->jobContactState);
                    break;

                case 'JOB_CON_PER_COUNTRY':
                    $item->setAttribute('value', $this->jobContactCountry);
                    break;

                case 'JOB_CON_PER_PHONE_H':
                    $item->setAttribute('value', $this->jobContactHomeNumber);
                    break;

                case 'JOB_CON_PER_PHONE_C':
                    $item->setAttribute('value', $this->jobContactCellNumber);
                    break;

                case 'JOB_CON_PER_PHONE_P':
                    $item->setAttribute('value', $this->jobContactPhoneNumber);
                    break;

                case 'JOB_CON_PER_PHONE_O':
                    $item->setAttribute('value', $this->jobContactOfficeNumber);
                    break;

                case 'JOB_CON_PER_PHONE_OT':
                    $item->setAttribute('value', $this->jobContactOtherNumber);
                    break;

                case 'JOB_CON_PER_PHONE_F':
                    $item->setAttribute('value', $this->jobContactFaxNumber);
                    break;

                case 'JOB_CON_PER_PHONE':
                    $item->setAttribute('value', $this->jobContactNumber);
                    break;

                case 'JOB_CONTRACT_SIGNED_DATE':
                    $item->setAttribute('value', $this->jobContractSignedDate);
                    break;

                case 'JOB_INS_COMPANY_NAME':
                    $item->setAttribute('value', $this->jobInsuranceCompanyName);
                    break;

                case 'JOB_INS_CLAIM_NO':
                    $item->setAttribute('value', $this->jobInsuranceNumber);
                    break;

                case 'JOB_INS_COMPANY_FAX':
                    $item->setAttribute('value', $this->jobInsuranceFax);
                    break;

                case 'JOB_INS_COMPANY_EMAIL':
                    $item->setAttribute('value', $this->jobInsuranceEmail);
                    break;

                case 'JOB_INS_COMPANY_PHONE':
                    $item->setAttribute('value', $this->jobInsurancePhone);
                    break;

                case 'JOB_INS_ADJUSTER_NAME':
                    $item->setAttribute('value', $this->jobInsuranceAdjusterName);
                    break;

                case 'JOB_INS_ADJUSTER_PHONE':
                    $item->setAttribute('value', $this->jobInsuranceAdjusterPhone);
                    break;

                case 'JOB_INS_ADJUSTER_EMAIL':
                    $item->setAttribute('value', $this->jobInsuranceAdjusterEmail);
                    break;

                case 'JOB_INS_RCV':
                    $item->setAttribute('value', $this->jobInsuranceRCV);
                    break;

                case 'JOB_INS_POLICY_NUMBER':
                    $item->setAttribute('value', $this->jobInsurancePolicyNumber);
                    break;

                case 'JOB_INS_DEDUCTABLE_AMOUNT':
                    $item->setAttribute('value', $this->jobInsuranceDeductableAmount);
                    break;

                case 'JOB_INS_CONTINGENCY_CONTRACT_SIGNED_DATE':
                    $item->setAttribute('value', $this->jobInsuranceContingencyContractSignedDate);
                    break;

                case 'JOB_INS_DATE_OF_LOSS':
                    $item->setAttribute('value', $this->jobInsuranceDateOfLoss);
                    break;

                case 'JOB_INS_ACV':
                    $item->setAttribute('value', $this->jobInsuranceAcv);
                    break;

                case 'JOB_INS_SUPPLEMENT':
                    $item->setAttribute('value', $this->jobInsuranceSupplement);
                    break;
                case 'JOB_INS_NET_CLAIM':
                    $item->setAttribute('value', $this->jobInsuranceNetClaim);
                    break;
                case 'JOB_INS_DEPRECIATION':
                    $item->setAttribute('value', $this->jobInsuranceDepreciation);
                    break;

                case 'JOB_INS_TOTAL':
                    $item->setAttribute('value', $this->jobInsuranceTotal);
                    break;

                case 'JOB_INS_UPGRADES':
                    $item->setAttribute('value', $this->jobInsuranceUpgrades);
                    break;

                case 'CONTRACTOR_LICENSE_NUMBER_1':
                    $item->setAttribute('value', $this->companyLicenseNumber1);
                    break;
                case 'CONTRACTOR_LICENSE_NUMBER_2':
                    $item->setAttribute('value', $this->companyLicenseNumber2);
                    break;
                case 'CONTRACTOR_LICENSE_NUMBER_3':
                    $item->setAttribute('value', $this->companyLicenseNumber3);
                    break;
                case 'CONTRACTOR_LICENSE_NUMBERS':
                    $item->setAttribute('value', $this->companyLicenseNumbersAll);
                    break;
                case 'CONTRACTOR_LICENSE_NUMBER':
                    $item->setAttribute('value', $this->companyLicenseNumber);
                    break;
                case 'COMPANY_EMAIL':
                    $item->setAttribute('value', $this->companyEmail);
                    break;
                case 'COMPANY_ADDITIONAL_EMAIL':
                    $item->setAttribute('value', $this->companyAdditionalEmail);
                    break;
                case 'COMPANY_PHONE':
                    $item->setAttribute('value', $this->companyPhone);
                    break;
                case 'COMPANY_ADDITIONAL_PHONE':
                    $item->setAttribute('value', $this->companyAdditionalPhone);
                    break;
                case 'COMPANY_FULL_ADDRESS':
                    $item->setAttribute('value', $this->companyFullAddress);
                    break;
                case 'COMPANY_WEBSITE_URL':
                    $item->setAttribute('value', $this->companyWebsiteUrl);
                    break;
                case 'CUSTOMER_RESIDENTIAL_COMPANY_NAME':
                    $item->setAttribute('value', $this->customerCompanyNameResidential);
                    break;
                case 'JOB_PURCHASE_ORDER_NUMBER':
                    $item->setAttribute('value', $this->jobPurchaseOrderNumber);
                    break;
                case 'JOB_MATERIAL_DELIVERY_DATE':
                    $item->setAttribute('value', $this->jobMaterialDeliveryDate);
                    break;
                case 'JOB_ESTIMATOR_EMAIL':
                    $item->setAttribute('value', $this->jobEstimatorsEmail);
                    break;
                case 'JOB_ESTIMATOR_PHONE_NUMBER':
                    $item->setAttribute('value', $this->jobEstimatorsPhone);
                    break;
                case 'COMPANY_NAME':
                    $item->setAttribute('value', $this->companyName);
                    break;
                case 'JOB_SCHEDULE_START_DATE':
                    $item->setAttribute('value', $this->jobScheduleStartDate);
                    break;
                case 'JOB_SCHEDULE_END_DATE':
                    $item->setAttribute('value', $this->jobScheduleEndDate);
                    break;
                case 'JOB_APPOINTMENT_START_DATE':
                    $item->setAttribute('value', $this->jobAppointmentStartDate);
                    break;
                case 'JOB_APPOINTMENT_END_DATE':
                    $item->setAttribute('value', $this->jobAppointmentEndDate);
                    break;
            }
        }

        //signature auto fill
        $selector = new \DOMXPath($dom);

        foreach ($selector->query('//div[contains(attribute::class, "proposal-counter")]') as $e) {
            $e->nodeValue = $this->serialNumber;
        }

        $html = $dom->saveHTML($dom->documentElement);
        if (!$html) {
            throw new Exception(Lang::get('response.error.template_content_empty'));
        }

        return $html;
    }

    /**
     * Fill serial number element
     * @param  String $content HTML content
     * @return Content
     */
    public function fillSerialNumberElement($content)
    {
        if (!$this->hasAttributes) {
            throw new Exception("Attributes not set.");
        }

        $dom = new DOMDocument();

        $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $selector = new \DOMXPath($dom);
        foreach ($selector->query('//div[contains(attribute::class, "proposal-counter")]') as $e) {
            $e->nodeValue = $this->serialNumber;
        }

        $html = $dom->saveHTML($dom->documentElement);
        if (!$html) {
            throw new Exception(Lang::get('response.error.template_content_empty'));
        }

        return $html;
    }

    /**
     * Update Template Value of Proposal
     * @param  $template     HTML Content
     * @param  $dataElements Data
     * @return Content
     */
    public function fillTemplateValue($template, $dataElements)
    {
        $dom = new DOMDocument();

        $dom->loadHTML(mb_convert_encoding($template, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $dom->normalizeDocument();

        // Value Updation of Tags.
        $q = new \DOMXPath($dom);
        foreach ($dataElements as $key => $value) {
            $attribute = $value['attribute'];
            $attributeValue = $value['attribute_value'];
            $tag = $value['tag'];
            $data = $value['value'];
            $updateAttribute = !empty($value['update_attribute']) ? $value['update_attribute'] : null;
            $updateHtml = !empty($value['update_html']) ? $value['update_html'] : null;

            foreach ($q->query('//' . $tag . '[@' . $attribute . '="' . $attributeValue . '"]') as $item) {
                if ($tag == 'input') {
                    if(!$updateAttribute) {
						$updateAttribute = 'value';
					}
					$item->setAttribute($updateAttribute, $data);
                } elseif ($updateAttribute) {
                    $item->setAttribute($updateAttribute, $data);
                } elseif ($updateHtml) {
                    $fragment = $dom->createDocumentFragment();
                    $fragment->appendXML($data);

                    while ($item->hasChildNodes()) {
                        $item->removeChild($item->firstChild);
                    }

                    $item->appendChild($fragment);
                } else {
                    $item->nodeValue = $data;
                }
            }
        }

        $html = $dom->saveHTML($dom->normalizeDocument());

        if (!$html) {
            throw new Exception(Lang::get('response.error.template_content_empty'));
        }

        return $html;
    }
}
