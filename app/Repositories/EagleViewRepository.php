<?php

namespace App\Repositories;

use App\Models\EVClient;
use App\Models\EVOrder;
use App\Models\EVReport;
use App\Models\EVStatus;
use App\Services\Contexts\Context;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use App\Services\Measurement\MeasurementService;


class EagleViewRepository extends ScopedRepository
{

    protected $scope;

    public function __construct(Context $scope, MeasurementService $measurementService)
    {
        $this->scope = $scope;
        $this->measurementService = $measurementService;
    }

    public function saveClient($username, $accessToken, $refreshToken, $clientId, $tokenExpirationDate)
    {
        $tokenExpirationDate = Carbon::parse($tokenExpirationDate)->toDateTimeString();
        $client = EVClient::whereCompanyId($this->scope->id())->whereUsername($username)->first();

        if($client) {
            $client->username               = $username;
            $client->access_token           = $accessToken;
            $client->refresh_token          = $refreshToken;
            $client->client_id              = $clientId;
            $client->token_expiration_date  = $tokenExpirationDate;            
            $client->save();
        } else {
            $client = EVClient::create([
                'company_id'            => $this->scope->id(),
                'username'              => $username,
                'access_token'          => $accessToken,
                'refresh_token'         => $refreshToken,
                'client_id'             => $clientId,
                'token_expiration_date' => $tokenExpirationDate,
            ]);
        }

        return $client;
    }

    public function deleteClient()
    {
        $client = EVClient::whereCompanyId($this->scope->id())->delete();
        return true;
    }

    public function saveOrder($customerData, $productData)
    {
        $evOrder = new EVOrder;
        $evOrder->address = $customerData;
        $evOrder->company_id = $this->scope->id();
    }

    public function getClient()
    {
        return EVClient::where('company_id', $this->scope->id())
            ->firstOrFail();
    }

    public function getOrders($filters = [])
    {
        $with = $this->includeData($filters);
        $orders = EVOrder::with($with)->where('company_id', $this->scope->id())
            ->orderBy('id', 'desc');
        $this->applyOrdersFilters($orders, $filters);

        return $orders;
    }

    public function getOrderByReportId($reportId)
    {
        $order = EVOrder::whereReportId($reportId)
            ->firstOrFail();
        return $order;
    }

    public function saveReport($reportId, $fileTypeId, $fileName, $filePath, $fileSize, $fileMimeType)
    {
        $order = $this->getOrderByReportId($reportId);
        $report = EVReport::create([
            'report_id' => $reportId,
            'file_type_id' => $fileTypeId,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'file_size' => $fileSize,
            'file_mime_type' => $fileMimeType,
        ]);

        $measurement = $order->measurement;

        // save measurement..
        if(($report->file_mime_type == EVReport::PDF) && $measurement) {
            $measurement->is_file = true;
            $measurement->file_name = $fileName;
            $measurement->ev_file_type_id = $fileTypeId;
            $measurement->file_path = $filePath;
            $measurement->file_size = $fileSize;
            $measurement->file_mime_type = $fileMimeType;
            $measurement->save();
        }

        if(($fileMimeType == EVReport::JSON) && ($measurement)) {
            $this->measurementService->updateEagleviewMeasurement($measurement, $report->file_path, $fileTypeId);
        }

        return $report;
    }

    public function getReportsByJob($jobId)
    {
        $reportIds = $this->getOrders(['job_id' => $jobId])->pluck('report_id')->toArray();
        $reports = EVReport::whereIn('report_id', $reportIds)->whereFileMimeType(EVReport::PDF)->get();
        return $reports;
    }

    public function updateOrderStatus(EVOrder $order, $status, $subStatus)
    {
        $order->status_id = $status;
        $order->sub_status_id = $subStatus;
        $order->save();

        if ($status == EVStatus::COMPLETED) {
            $taskRepo = App::make(\App\Repositories\TasksRepository::class);
            $userId = $order->created_by;
            $customer = $order->customer;
            $job = $order->job;
            $title = "EagleView Estimate Received";
            $data['customer_id'] = $customer->id;
            $data['job_id'] = $job->id;
            $note = "Estimate ready to be reviewed for " . $customer->first_name . " " . $customer->last_name . " / " . $job->number;
            $data['notes'] = $note;
            $data['company_id'] = $order->company_id;
            $data['due_date'] = Carbon::now()->toDateString();
            $taskRepo->createTask($userId, $userId, $title, $data);
        }
    }

    public function getProductListFromOrders()
    {
        $list = EVOrder::where('company_id', $this->scope->id())
            ->distinct()
            ->pluck('product_type')->toArray();
        return $list;
    }

    /***************** Private section ********************/

    private function applyOrdersFilters($query, $filters)
    {

        if (ine($filters, 'report_id')) {
            $query->whereReportId($filters['report_id']);
        }

        if (ine($filters, 'claim_number')) {
            $query->whereClaimNumber($filters['claim_number']);
        }

        if (ine($filters, 'product_type')) {
            $query->whereProductType($filters['product_type']);
        }

        if (ine($filters, 'status_id')) {
            $query->whereStatusId($filters['status_id']);
        }

        // date range
        if (ine($filters, 'start_date')) {
            $start = $filters['start_date'];
            $query->whereRaw("DATE_FORMAT(created_at, '%Y-%m-%d') >= '$start'");
        }

        if (ine($filters, 'end_date')) {
            $end = $filters['end_date'];
            $query->whereRaw("DATE_FORMAT(created_at, '%Y-%m-%d') <= '$end'");
        }

        if (ine($filters, 'job_id')) {
            $query->whereJobId($filters['job_id']);
        }

        if (ine($filters, 'customer_id')) {
            $query->whereCustomerId($filters['customer_id']);
        }

        // check sub contractor user
        if(\Auth::user()->isSubContractorPrime()) {
            $query->whereIn('ev_orders.job_id', function($query) {
                $query->select('job_sub_contractor.job_id')
                    ->from('job_sub_contractor')
                    ->where('job_sub_contractor.sub_contractor_id', \Auth::id());
            });
        }
    }

    private function includeData($filters)
    {
        $with = [
            'job',
            'pdfReport',
            'status',
            'subStatus',
        ];

        if(!ine($filters, 'includes')) return $with;

        $includes = (array)$filters['includes'];

        if(in_array('report_files', $includes)) {
            $with[] = 'allReports';
        }

        return $with;
    }
}
