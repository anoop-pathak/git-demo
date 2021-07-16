<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\Appointment;
use App\Models\ChangeOrder;
use App\Models\Company;
use App\Models\InvoicePayment;
use App\Models\JobWorkflowHistory;
use App\Models\Resource;
use App\Models\Trade;
use App\Models\User;
use App\Repositories\JobRepository;
use App\Repositories\WorkflowRepository;
use App\Services\Contexts\Context;
use FlySystem;
use App\Transformers\JobsExportTransformer;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Request;
use PDF;
use Illuminate\Support\Facades\Lang;
use Excel;
use Sorskod\Larasponse\Larasponse;

class JobsExportController extends Controller
{

    protected $transformer;
    protected $repo;
    protected $workflowRepo;
    protected $scope;

    public function __construct(Larasponse $response, JobRepository $repo, WorkflowRepository $workflowRepo, Context $scope)
    {
        $this->response = $response;
        $this->repo = $repo;
        $this->workflowRepo = $workflowRepo;
        $this->scope = $scope;
        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }

        parent::__construct();
    }

    public function export()
    {
        $input = Request::all();
        $jobs = $this->repo->getFilteredJobs($input);
        $trades = Trade::pluck('name', 'id')->toArray();
        $company_id = $this->scope->id();
        $company = Company::find($company_id);
        $users = User::where('company_id', $company_id)->select('id', DB::raw("CONCAT(first_name,' ',last_name) as fname"))->pluck('fname', 'id')->toArray();
        $stages = $this->workflowRepo->getActiveWorkflow($company_id)->stages->pluck('name', 'code')->toArray();
        $jobs = $jobs->with([
            'customer',
            'customer.address',
            'customer.address.state',
            'customer.address.country',
            'customer.phones',
            'customer.flags.color',
            'customer.rep',
            'customer.secondaryNameContact',
            'address.state',
            'address.country',
            'trades',
            'workTypes',
            'jobWorkflow',
            'flags.color',
            'todayAppointments',
            'upcomingAppointments',
            'reps',
            // 'labours',
            'subContractors',
            'estimators',
            'projects' => function($query) use($input){
                if(ine($input, 'estimator_ids')) {
                    $estimatorIds = $input['estimator_ids'];
                    $query->whereIn('jobs.id',function($query) use($estimatorIds){
                        $query->select("job_id")->from('job_estimator')->whereIn('rep_id', (array)$estimatorIds);
                    });
                }
                if(ine($input, 'job_rep_ids')) {
                    $query->whereIn('jobs.id',function($query) use($input){
                        $query->select("job_id")->from('job_rep')->whereIn('rep_id', (array)$input['job_rep_ids']);
                    });
                }
                if(ine($input, 'sub_ids')) {
                    $query->whereIn('jobs.id',function($query) use($input){
                        $query->select("job_id")->from('job_sub_contractor')->whereIn('sub_contractor_id', (array)$input['sub_ids']);
                    });
                }
                if(ine($input, 'trades')) {
                    $query->whereIn('jobs.id',function($query) use($input){
                        $query->select("job_id")->from('job_trade')->whereIn('trade_id', (array)$input['trades']);
                    });
                }
                if(ine($input, 'work_types')) {
                    $query->whereIn('jobs.id',function($query) use($input){
                        $query->select("job_id")->from('job_work_types')->whereIn('job_type_id', (array)$input['work_types']);
                    });
                }
            }]
        )->get();

        //get appointment count, by default is 0
        $appointmentCount = 0;
        if (ine($input, 'appointment_count')
            && ine($input, 'user_id')) {
            $appointment = Appointment::recurring()->users((array)$input['user_id'])->whereCompanyId($company_id);
            //set flag for appointment date
            $lostJob = false;

            if (ine($input, 'lost_job_from') || ine($input, 'lost_job_to')) {
                $start = ine($input, 'lost_job_from') ? $input['lost_job_from'] : null;
                $end = ine($input, 'lost_job_to') ? $input['lost_job_to'] : null;
                $appointment->dateRange($start, $end);
                $lostJob = true;
            }

            // if lost job flag true then this filters not working
            if (!($lostJob) && (ine($input, 'awarded_from') || ine($input, 'awarded_to'))) {
                $start = ine($input, 'awarded_from') ? $input['awarded_from'] : null;
                $end = ine($input, 'awarded_to') ? $input['awarded_to'] : null;
                $appointment->dateRange($start, $end);
            }

            $appointmentCount = $appointment->count();
        }

        // if(ine($input,'mode') && $input['mode'] == 'portrait') {
        // 	$mode = 'portrait';
        // 	$view = 'jobs.jobs_export_portrait';
        // }
        $mode = 'landscape';
        $view = 'jobs.jobs_export_landscape';

        $contents = view($view, [
            'jobs' => $jobs,
            'users' => $users,
            'trades' => $trades,
            'stages' => $stages,
            'filters' => $input,
            'company' => $company,
            'appointment_count' => $appointmentCount,
            'company_country_code' => $company->country->code
        ])->render();
        $pdf = PDF::loadHTML($contents)->setPaper('a4')->setOrientation($mode);
        $pdf->setOption('dpi', 200);
        return $pdf->stream('jobs.pdf');
    }

    /**
     * Get pdf of job or save as attachemnt
     *
     * @param  $id [job id]
     * @return
     */
    public function job_detail_page_print($id)
    {
        $input = Request::onlyLegacy('save_as_attachment');
        $company_id = $this->scope->id();
        $isProject = false;
        $with = [
            'address',
            'customer.address',
            'customer.address.state',
            'customer.address.country',
            'customer.phones',
            'customer.rep',
            'customer.appointments',
            'customer.phones',
            'customer.flags.color',
            'address.state',
            'address.country',
            'trades',
            'jobWorkflow',
            'workflow.stages',
            'appointments',
            'flags.color',
            'projects' => function ($query) {
                $query->withoutArchived();
                if(\Auth::user()->isSubContractorPrime()) {
                    $query->own(\Auth::id());
                }
            },
            'reps',
            'subContractors'
        ];
        $job = $this->repo->getById($id, $with);

        if ($job->isProject()) {
            $isProject = true;
            $job = $job->parentJob()->with([
                'projects' => function ($query) use ($id) {
                    $query->whereId($id);
                }
            ])->first();
        }


        $company = Company::find($company_id);
        $completedStages = JobWorkflowHistory::where('job_id', $id)->pluck('created_at', 'stage')->toArray();
        $contents = view('jobs.job_detail_page', [
            'job' => $job,
            'company' => $company,
            'completed_stages' => $completedStages,
            'is_project' => $isProject,
            'company_country_code' => $company->country->code
        ])->render();

        $pdf = PDF::loadHTML($contents)
            ->setPaper('a4')
            ->setOrientation('portrait')
            ->setOption('dpi', 200);

        if (!ine($input, 'save_as_attachment')) {
            return $pdf->stream('job.pdf');
        }

        $attachment = $this->saveAsAttachment($pdf, 'job.pdf');

        return ApiResponse::success([
            'message' => Lang::get('response.success.file_uploaded'),
            'file' => $attachment,
        ]);
    }

    public function getCumulativeInvoice($jobId)
    {
        $input = Request::all();
        $job = $this->repo->getById($jobId, ['jobInvoices']);

        if (!$job->jobInvoices->count()) {
            return ApiResponse::errorGeneral(
                trans('response.error.not_created', ['attribute' => 'Job invoice'])
            );
        }

        $financialCalculation = $job->financialCalculation;
        $changeOrders = ChangeOrder::where('job_id', $job->id)->whereNull('canceled')->get();
        $invoiceIds = $job->invoices->pluck('id')->toArray();

        $InvoicePaymentObj    = InvoicePayment::whereIn('invoice_id', $invoiceIds);
        $forAppliedPayment    = clone $InvoicePaymentObj;
        $forAppliedCredit     = clone $InvoicePaymentObj;
        $totalAppliedPayment  = $forAppliedPayment->whereNull('credit_id')->sum('amount');
        $totalAppliedCredit   = $forAppliedCredit->whereNotNull('credit_id')->sum('amount');
        $totalPayments        = InvoicePayment::whereIn('invoice_id', $invoiceIds)
            ->whereNull('credit_id')
            ->get();
        $totalCreditPayments      = InvoicePayment::whereIn('invoice_id', $invoiceIds)
            ->whereNotNull('credit_id')
            ->get();

        $data = [
            'job'                   => $job,
            'company'               => $job->company,
            'customer'              => $job->customer,
            'financial_calculation' => $financialCalculation,
            'total_applied_payment' => $totalAppliedPayment,
            'total_applied_credit'  => $totalAppliedCredit,
            'invoices'              => $job->jobInvoices,
            'changeOrders'          => $changeOrders,
            'total_payments'        => $totalPayments,
            'total_credit_payments' => $totalCreditPayments,
        ];

        $contents = view('jobs.cumulative-invoice', $data)->render();

        $pdf = PDF::loadHTML($contents)->setOption('page-size','A4')
            ->setOption('margin-left', 0)
            ->setOption('margin-right', 0)
            ->setOption('margin-top', '0.8cm')
            ->setOption('margin-bottom', '0.8cm');


        if (!ine($input, 'save_as_attachment')) {
            return $pdf->stream('cumulative_invoice.pdf');
        }

        $attachment = $this->saveAsAttachment($pdf, 'cumulative_invoice.pdf');

        return ApiResponse::success([
            'message' => Lang::get('response.success.file_uploaded'),
            'file' => $attachment,
        ]);
    }

    /**
     * Export Csv File
     * Get jobs/export_csv
     * @return Excel File
     */
    public function exportCsvFile()
    {
        switchDBConnection('mysql2');
        set_time_limit(0);
        $input = Request::all();
        try {
            $repo = App::make(\App\Repositories\JobsListingRepository::class);
            $jobs = $repo->getFilteredJobs($input, $sortable = true, $eagerLoading = false);
            $jobs->with([
                'division',
                'trades',
                'jobTypes',
                'workTypes',
                'customer.address.state',
                'customer.rep',
                'jobWorkflow',
                'subContractors',
                'estimators',
                'reps',
                'customer.phones',
                'address.state',
                'address.country',
                'financialCalculation' => function ($query) {
                    $query->whereMultiJob(false);
                },
                'primaryJobContact.contactPrimaryEmail',
                'primaryJobContact.phones',
				'customer.referredByReferral'
            ]);
            $jobs = $jobs->get();
            $jobs = $this->response->collection($jobs, new JobsExportTransformer);
            Excel::create('Jobs', function ($excel) use ($jobs) {
                $excel->sheet('sheet1', function ($sheet) use ($jobs) {
                    $sheet->fromArray($jobs['data']);
                });
            })->export('csv');
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    /**
     * Save as attachment
     *
     * @param  $pdfObject [pdf object of job]
     * @param  $name [pdf name]
     * @return
     */
    private function saveAsAttachment($pdfObject, $name)
    {
        $rootDir = $this->getRootDir();
        $rootPath = \config('resources.BASE_PATH') . $rootDir->path;
        $physicalName = \Carbon\Carbon::now()->timestamp . '_' . $name;
        $filePath = $rootPath . '/' . $physicalName;
        $mimeType = 'application/pdf';

        /* save pdf */
        FlySystem::put($filePath, $pdfObject->output(), ['ContentType' => $mimeType]);

        $size = FlySystem::getSize($filePath);
        $mimeType = 'application/pdf';
        $resourcesRepo = App::make(\App\Repositories\ResourcesRepository::class);
        $resource = $resourcesRepo->createFile($name, $rootDir, $mimeType, $size, $physicalName);

        return $resource;
    }

    /**
     * Get resources root directory
     *
     * @return
     */
    private function getRootDir()
    {
        $parentDir = Resource::name(Resource::EMAIL_ATTACHMENTS)
            ->company($this->scope->id())
            ->first();

        if (!$parentDir) {
            $resourceService = App::make(\App\Resources\ResourceServices::class);
            $root = Resource::companyRoot($this->scope->id());
            $parentDir = $resourceService->createDir(Resource::EMAIL_ATTACHMENTS, $root->id);
        }

        return $parentDir;
    }
}
