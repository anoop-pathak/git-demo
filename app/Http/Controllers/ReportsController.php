<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Services\Reports\CompanyPerformenceReport;
use App\Services\Reports\JobListing;
use App\Services\Reports\MarketingSourceReport;
use App\Services\Reports\MasterListReport;
use App\Services\Reports\MovedToStageReport;
use App\Services\Reports\OwedToCompanyReport;
use App\Services\Reports\ProfitLossAnalysisReport;
use App\Services\Reports\ProposalsReport;
use App\Services\Reports\SalesPerformenceReport;
use App\Services\Reports\SalesPerformenceSummaryReport;
use Illuminate\Support\Facades\App;
use Request;
use Illuminate\Support\Facades\Validator;
use App\Services\Reports\TotalSalesReport;
use App\Services\Reports\ProjectSourceReport;
use App\Services\Reports\CommissionsReport;
use App\Services\Reports\SalesTaxReport;
use App\Services\Reports\JobInvoiceListing;
use App\Services\Reports\ReferralSourceReportCSVExport;

class ReportsController extends ApiController
{
    /* SalesPerformenceReport class Instance */
    protected $salesPerformence;

    /* MarketingSourceReport class Instance */
    protected $marketingSource;

    /* CompanyPerformenceReport class Instance */
    protected $companyPerformence;

    /* OwedToCompanyReport class Instance */
    protected $owedToCompany;

    /* date format rule (2016-1-31) */
    protected $dateRules = [
        'start_date' => 'date_format:Y-m-d',
        'end_date' => 'date_format:Y-m-d',
    ];

    protected $rules = [
		'user_id'	=> 'required',
	];

    public function __construct(
        SalesPerformenceReport $salesPerformence,
        MarketingSourceReport $marketingSource,
        CompanyPerformenceReport $companyPerformence,
        OwedToCompanyReport $owedToCompany,
        ProposalsReport $proposals,
        MasterListReport $masterList,
        MovedToStageReport $movedToStage,
        SalesPerformenceSummaryReport $salesPerformenceSummaryReport,
        ProfitLossAnalysisReport $profitLossAnalysisReport,
        JobListing $jobListing,
        CommissionsReport $commissionsReport,
        TotalSalesReport $totalSales,
        ProjectSourceReport $projectSource,
        SalesTaxReport $salesTax,
		JobInvoiceListing $jobInvoiceListing,
		ReferralSourceReportCSVExport $exportCsvReport
    ) {

        $this->salesPerformence = $salesPerformence;
        $this->marketingSource = $marketingSource;
        $this->companyPerformence = $companyPerformence;
        $this->owedToCompany = $owedToCompany;
        $this->proposals = $proposals;
        $this->masterList = $masterList;
        $this->movedToStage = $movedToStage;
        $this->salesPerformenceSummaryReport = $salesPerformenceSummaryReport;
        $this->jobListing = $jobListing;
        $this->profitLossAnalysisReport = $profitLossAnalysisReport;
        $this->commissionsReport = $commissionsReport;
        $this->totalSales = $totalSales;
        $this->projectSource = $projectSource;
        $this->salesTax = $salesTax;
		$this->jobInvoiceListing = $jobInvoiceListing;
		$this->exportCsvReport = $exportCsvReport;

        Request::merge(['disable_division' => true]);
        parent::__construct();
        // change db connection for report
        switchDBConnection('mysql2');
    }


    /**
     * get data for sales performance report
     * @param
     * @return $data(arary)
     */
    public function getSalesPerformance()
    {
        $input = Request::all();

        $validator = Validator::make($input, $this->dateRules);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $input['with_archived'] = true;
        $data = $this->salesPerformence->get($input);

        return ApiResponse::success($data);
    }

    public function getSalesPerformanceBySalesman()
    {
        $input = Request::all();
        $validator = Validator::make($input, $this->rules, $this->dateRules);
        if( $validator->fails() ) {

            return ApiResponse::validation($validator);
        }

        $data = $this->salesPerformence->getBySalesman($input);
        return ApiResponse::success($data);
    }

    /**
     * get data for company performance report
     * @param
     * @return $data(arary)
     */
    public function getCompanyPerformance()
    {
        Request::merge(['disable_division' => false]);
        $input = Request::all();

        $validator = Validator::make($input, $this->dateRules);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $input['with_archived'] = true;
        $data = $this->companyPerformence->get($input);

        return ApiResponse::success($data);
    }

    /**
     * get data for marketing source report
     * @param
     * @return $data(arary)
     */
    public function getMarketingSource()
    {
        $input = Request::all();

        $validator = Validator::make($input, $this->dateRules);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $input['with_archived'] = true;
        $data = $this->marketingSource->get($input);

        if(ine($input, 'csv_export')){
			return $this->exportCsvReport->getCSVReport($data);
		}

        return ApiResponse::success($data);
    }

    /**
     * get data for 'owed to company' report
     * @param
     * @return $data(arary)
     */
    public function getOwedToCompany()
    {
        $input = Request::all();
        Request::merge(['disable_division' => false]);

        $validator = Validator::make($input, $this->dateRules);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $input['with_archived'] = true;
        $data = $this->owedToCompany->get($input);

        if(ine($input, 'export_csv')){
            return $data;
        }

        return ApiResponse::success($data);
    }

    /**
     * get data for 'Proposals' report
     * @param
     * @return $data(arary)
     */
    public function getProposals()
    {
        $input = Request::all();

        $validator = Validator::make($input, $this->dateRules);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $data = $this->proposals->get($input);

        return ApiResponse::success($data);
    }

    /**
     * get data for 'Commissions' report
     * @param
     * @return $data(arary)
     */
    public function getCommissionsReport()
    {
        $user = \Auth::user();
        if(!$user->isAuthority() && !$user->isStandardUser()) {
            return ApiResponse::errorForbidden();
        }

        $input = Request::all();

        $rules = [
            'user_id' => 'required_if:for_awarded_job,1'
        ];

        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }


        $data = $this->commissionsReport->get($input);

        return ApiResponse::success($data);
    }

    /**
     * Get Master List Report
     * Get reports/master_list
     * @return Response
     */
    public function getMasterList()
    {
        $input = Request::all();
        $data = $this->masterList->get($input);

        if(ine($input, 'pdf_print') || ine($input, 'csv_export')){
            return $data;
        }

        return ApiResponse::success($data);
    }

    /**
     * Get moved to stage report
     * Get reports/moved_to_stage
     * @return Report
     */
    public function getMovedToStageReport()
    {
        $input = Request::all();
        $data = $this->movedToStage->get($input);

        if(ine($input, 'csv_export')){
            return $data;
        }

        return ApiResponse::success($data);
    }

    /**
     * Get sale performance summary report
     * @return Response
     */
    public function getSalesPerformanceSummaryReport()
    {
        $input = Request::all();

        $data = $this->salesPerformenceSummaryReport->get($input);
        if(ine($input, 'csv_export')){
            return $data;
        }

        return ApiResponse::success($data);
    }

    /**
     * Get job listing
     * Get reports/job_listing
     * @return Listing
     */
    public function jobListing()
    {
        $input = Request::all();
        $data = $this->jobListing->get($input);

        if(ine($input, 'pdf_print')){
            return $data;
        }

        return ApiResponse::success($data);
    }

    /**
     * Get profit loss analysis report
     * Get reports/profit_loss_analysis_report
     * @return report
     */
    public function getProfitLossAnalysisReport()
    {
        $input = Request::all();
        $data = $this->profitLossAnalysisReport->get($input);

        if(ine($input, 'csv_export')){
            return $data;
        }
        return ApiResponse::success($data);
    }

    /**
	 * Get profit loss analysis report total
	 * Get reports/profit_loss_analysis_report total
	 * @return report total
	 */
	public function getProfitLossAnalysisReportTotal()
	{
		$input = Request::all();
		$data = $this->profitLossAnalysisReport->getTotal($input);

		return ApiResponse::success(['data' => $data]);
	}

    /**
     * Get total sales report
     * Get reports/total_sales_report
     * @return report
     */
    public function getTotalSalesReport()
    {
        $input = Request::all();
        $data = $this->totalSales->get($input);
        if(ine($input, 'csv_export')){
			return $data;
		}
        return ApiResponse::success($data);
    }

    /**
     * Get project source report
     * Get reports/project_source
     * @return report
     */
    public function getProjectSourceReport()
    {
        $input = Request::all();
        $data = $this->projectSource->get($input);
        if(ine($input, 'csv_export')){

			return $this->exportCsvReport->getCSVReport($data, true);
		}

		return ApiResponse::success($data);
	}

	/**
	 * Get sales tax report
	 * Get reports/total_sales_report
	 * @return report
	 */
	public function getSalesTaxReport()
	{
		Request::merge(['disable_division' => false]);
		$input = Request::all();
		$data = $this->salesTax->get($input);

		return ApiResponse::success($data);
	}

	/**
	 * Get Job Invoice Report
	 * @return report
	 */
	public function getJobInvoiceListing()
	{
		$input = Request::all();
		$data = $this->jobInvoiceListing->get($input);

		return ApiResponse::success($data);
	}

	/**
	 * Get Sum of All invoices
	 * @return Report
	 */
	public function getInvoiceListingSum()
	{
		$input = Request::all();
		$data = $this->jobInvoiceListing->getSumOfInvoices($input);

        return ApiResponse::success($data);
    }
}
