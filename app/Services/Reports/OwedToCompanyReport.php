<?php

namespace App\Services\Reports;

use App\Models\Company;
use App\Repositories\JobRepository;
use App\Services\Contexts\Context;
use App\Transformers\CompanyOwedReportCSVTransformer;
use App\Transformers\OwedToCompanyReportTransformer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Sorskod\Larasponse\Larasponse;
use Excel;

class OwedToCompanyReport extends AbstractReport
{

    protected $scope;
    protected $jobRepo;

    /* Larasponse class Instance */
    protected $response;

    function __construct(Context $scope, JobRepository $jobRepo, Larasponse $response)
    {
        set_time_limit(0);
        $this->scope = $scope;
        $this->jobRepo = $jobRepo;
        $this->response = $response;
    }

    public function get($filters = [])
    {
        //check JOB_AWARDED_STAGE is set
        $jobAwardedStage = $this->getJobAwardedStage();

        //set date filters
        $filters = $this->setDateFilter($filters);
        $filters['includes'][] = 'financial_details';

        if (ine($filters, 'export_csv')) {
            return $this->getData($filters);
        }

        $data = $this->getData($filters);
        // $filters['ageing'] = null;

        $data = $this->addSum($data, $filters);

        return $data;
    }

    /************* Private Section **************/

    private function getData($filters)
    {
        $company = Company::find($this->scope->id());
        $countryCode = $company->country->code;

        Config::set('company_country_code', $countryCode);

        // default receivable
        if (!ine($filters, 'financial_status')) {
            $filters['financial_status'] = 'receivable';
        }

        //apply job filters
        $jobs = $this->jobRepo->getFilteredJobs($filters);
        $jobs->awarded(); // only awarded jobs..
        $jobs->with([
            'invoices',
            'projects.invoices',
            'commissions.user',
            'commissions',
            'payments' => function ($query) {
                $query->excludeCanceled();
            },
            // 'paymentMethods',
			'trades',
			'workTypes',
			'division'
        ]);
        $jobs->ageing();
        $this->applyFilters($jobs, $filters);

        if (ine($filters, 'use_invoice_amount')) {
            $jobs->addSelect(DB::raw('SUM(job_invoice_amount + job_invoice_tax_amount + total_change_order_invoice_amount) as total_invoice_amount'));
        }

        if (ine($filters, 'export_csv')) {
            return $this->createCsv($jobs, $filters);
        }

        $limit = isset($filters['limit']) ? $filters['limit'] : config('jp.pagination_limit');

        if ($limit) {
            $data = $this->response->paginatedCollection($jobs->paginate($limit), new OwedToCompanyReportTransformer);
        } else {
            $data = $this->response->collection($jobs->get(), new OwedToCompanyReportTransformer);
        }

        if(ine($filters, 'duration') && ($filters['duration'] == 'since_inception')) {
            $data['meta']['company']['created_at'] = $this->scope->getSinceInceptionDate();
        }

        return $data;
    }

    private function addSum($data, $filters)
    {
        unset($filters['financial_status']);
        $joins = ['financial_calculation', 'awarded_stage'];

        if(ine($filters, 'customer_name')) {
            $joins[] = 'customers';
        }

        if(ine($filters, 'ageing')) {
            $joins[] = 'ageing_invoice';
        }
        $jobs = $this->jobRepo->getJobsQueryBuilder($filters, $joins);
        // $jobs->ageing();

        $this->applyFilters($jobs, $filters);

        // awarded jobs..
        $awardedJobs = clone $jobs;
        $awardedJobs->awarded();

        $total = $awardedJobs->get();

        $forRecievable = clone $awardedJobs;
        $forPayable = clone $awardedJobs;

        $forTotalInvoiceAmount = clone $awardedJobs;

        $totalAmountInPipLine = $jobs->notAwarded()->get()->sum('total_amount');
        $totalAmount   = $total->sum('total_amount');
        $totalInvoiceAmount = $forTotalInvoiceAmount->select(DB::raw('SUM(job_invoice_amount + job_invoice_tax_amount + total_change_order_invoice_amount) as total_invoice_amount'))->get()->sum('total_invoice_amount');
        $totalAmountWithoutInvoice = $totalAmount - $totalInvoiceAmount;
        $totalReceived = $total->sum('total_received_payemnt');
        $totalInvoiceReceivedAmount = $total->sum('total_invoice_received_payment');
        $totalReceivedWithoutInvoice = $totalReceived - $totalInvoiceReceivedAmount;
        // $totalChangeOrder = $total->sum('total_change_order_amount');
        $totalCredits       = $total->sum('total_credits');
        $unappliedCredits   = $total->sum('unapplied_credits');
        $appliedCredits     = $totalCredits - $unappliedCredits;
        $totalRefunds 		= $total->sum('total_refunds');

        // $receivableObj =  $forRecievable->where('pending_payment','>',0)->get();
        $receivableObj =  $forRecievable->where('pending_payment', '>', 0)
        ->selectRaw(
                'SUM(job_invoice_amount + job_invoice_tax_amount + total_change_order_invoice_amount - (total_invoice_received_payment + (total_credits - unapplied_credits))) as invoice_receivable ,
                SUM(total_amount - (total_received_payemnt + total_credits + total_refunds )) as total_receivable,
                SUM(pending_payment) as receivable,
                SUM(unapplied_credits) as unapplied_credits,
                SUM(total_credits) as total_credits,
                SUM(total_received_payemnt) as total_received_payment,
                SUM(job_invoice_amount + job_invoice_tax_amount + total_change_order_invoice_amount) as total_invoice_amount,
                SUM(total_refunds) as total_refunds'
                )->first();
        $receivableCredits = $receivableObj->unapplied_credits;
        $receivable = $receivableObj->receivable;
        $invoiceReceivable = $receivableObj->invoice_receivable;
        $receivableWithoutInvoice = $receivableObj->total_receivable - $invoiceReceivable;
        $netReceivable = $receivableObj->total_invoice_amount - ($receivableObj->total_received_payment + $receivableObj->total_credits);
        $payable =  $forPayable->where('pending_payment','<',0)->get()->sum('pending_payment');
        $data['sum']['total_payment']                       = numberFormat($totalAmount);
        $data['sum']['total_payment_without_invoice']       = numberFormat($totalAmountWithoutInvoice);
        $data['sum']['total_invoice_payment']               = numberFormat($totalInvoiceAmount);
        $data['sum']['received_invoice_payment']            = numberFormat($totalInvoiceReceivedAmount);
        $data['sum']['received_payment_without_invoice']    = numberFormat($totalReceivedWithoutInvoice);
        $data['sum']['total_recieved_payment']              = numberFormat($totalReceived);
        $data['sum']['total_credits']                       = numberFormat($totalCredits);
        $data['sum']['unapplied_credits']                   = numberFormat($unappliedCredits);
        $data['sum']['applied_credits']                     = numberFormat($appliedCredits);
        $data['sum']['payable']                             = numberFormat(abs($payable));
        // $data['sum']['total_change_order']        = numberFormat($totalChangeOrder);
        $data['sum']['receivable']                          = numberFormat($receivable);
        $data['sum']['invoice_receivable']                  = numberFormat($invoiceReceivable);
        $data['sum']['receivable_credits']                  = numberFormat($receivableCredits);
        $data['sum']['receivable_without_invoice']          = numberFormat($receivableWithoutInvoice);
        $data['sum']['net_receivable']                      = numberFormat($netReceivable);
        $data['sum']['total_amount_pipline']                = numberFormat($totalAmountInPipLine);
        $data['sum']['total_refunds'] 					    = numberFormat($totalRefunds);

        return $data;
    }

    /**
     * apply filters on sales performace report
     *
     * @param $query | $filters(array)
     * @return $query
     */
    protected function applyFilters($query, $filters = [])
    {
        //Ageing filter
        if (ine($filters, 'ageing')) {
            $date = $this->getAgeingDates($filters['ageing']);
            $startDate = $date['start_date'];
            $endDate = $date['end_date'];

            if (!$startDate) {
                $query->whereRaw("DATE_FORMAT(job_invoices.created_at, '%Y-%m-%d') < '$endDate'");
            } else {
                $query->whereRaw("DATE_FORMAT(job_invoices.created_at, '%Y-%m-%d') >= '$startDate'")
                    ->whereRaw("DATE_FORMAT(job_invoices.created_at, '%Y-%m-%d') <= '$endDate'");
            }
        }

        if(!ine($filters, 'user_id')) return;

        // get both customer reps and estimators
        if (ine($filters, 'for')
            && in_array('customer_rep', (array)$filters['for'])
            && in_array('estimator', (array)$filters['for'])
        ) {
            $query->where(function ($query) use ($filters) {
                $query->whereIn('jobs.customer_id', function ($query) use ($filters) {
                    $query->select('id')->from('customers')
                        ->whereIn('rep_id', (array)$filters['user_id']);
                })->orWhereIn('jobs.id', function ($query) use ($filters) {
                    $query->select('job_id')->from('job_estimator')
                        ->whereIn('rep_id', (array)$filters['user_id']);
                });
            });
        }

        //get only customer reps (default filter)
        if ((ine($filters, 'for')
            && in_array('customer_rep', (array)$filters['for'])
            && !in_array('estimator', (array)$filters['for']))) {
            $query->whereIn('jobs.customer_id', function ($query) use ($filters) {
                $query->select('id')->from('customers')
                    ->whereIn('rep_id', (array)$filters['user_id']);
            });
        }

        //get only estimators
        if ((ine($filters, 'for')
            && !in_array('customer_rep', (array)$filters['for'])
            && in_array('estimator', (array)$filters['for']))) {
            $query->whereIn('jobs.id', function ($query) use ($filters) {
                $query->select('job_id')->from('job_estimator')
                    ->whereIn('rep_id', (array)$filters['user_id']);
            });
        }

        // set deafult (customer_reps and estimators)
        if ((ine($filters, 'for')
                && !in_array('customer_rep', (array)$filters['for'])
                && !in_array('estimator', (array)$filters['for']))
            || (!ine($filters, 'for'))
        ) {
            $query->where(function($query) use($filters) {
                $query->whereIn('jobs.customer_id', function($query) use($filters){
                    $query->select('id')->from('customers')
                    ->whereIn('rep_id', (array)$filters['user_id']);
                })->orWhereIn('jobs.id', function($query) use($filters) {
                    $query->select('job_id')->from('job_estimator')
                        ->whereIn('rep_id', (array)$filters['user_id']);
                });
            });
        }
    }

    private function createCsv($jobs, $filters)
    {
        $limit = isset($filters['limit']) ? $filters['limit'] : config('jp.pagination_limit');

        if ($limit) {
            $data = $this->response->paginatedCollection($jobs->paginate($limit), new CompanyOwedReportCSVTransformer);
        } else {
            $data = $this->response->collection($jobs->get(), new CompanyOwedReportCSVTransformer);
        }

        return Excel::create('Account Receivable Report', function ($excel) use ($data) {
            $excel->sheet('sheet1', function ($sheet) use ($data) {
                $sheet->fromArray($data['data']);
            });
        })->export('csv');
    }


    /**
     * Get Ageing Dates
     * @param  String $ageing Ageing
     * @return Dates
     */
    private function getAgeingDates($ageing)
    {
        $todayDate = Carbon::now();
        $startDate = clone $todayDate;
        $endDate = clone $todayDate;

        switch ($ageing) {
            case 'more_than_30':
                $startDate->subDays(60);
                $endDate->subDays(31);
                break;
            case 'more_than_60':
                $startDate->subDays(90);
                $endDate->subDays(61);
                break;
            case 'more_than_90':
                $startDate->subDays(120);
                $endDate->subDays(91);
                break;
            case 'more_than_120':
                $startDate = null;
                $endDate->subDays(121);
                break;

            default:
                $startDate = $todayDate->subDays(30);
                break;
        }

        return [
            'start_date' => $startDate,
            'end_date' => $endDate
        ];
    }
}
