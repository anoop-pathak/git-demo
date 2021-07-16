<?php

namespace App\Handlers\Commands;

use App\Models\EVOrder;
use App\Repositories\EagleViewRepository;
use App\Repositories\MeasurementRepository;
use App\Services\Contexts\Context;
use App\Services\EagleView\EagleView;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EVOrderCommandHandler
{

    protected $command;

    protected $repo;

    public function __construct(EagleViewRepository $repo, Context $scope, EagleView $service)
    {
        $this->repo = $repo;
        $this->scope = $scope;
        $this->service = $service;
    }

    public function handle($command)
    {
        $this->command = $command;
        DB::beginTransaction();
        try {
            $evOrder = $this->saveOrder();
            $orderDetail = $this->service->placeOrder($this->command->orderData);
            $evOrder->report_id = $orderDetail['ReportIds'][0];
            $evOrder->ev_order_id = $orderDetail['OrderId'];
            $evOrder->save();
            $this->createJobMeasurement($evOrder);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
        DB::commit();

        return $evOrder;
    }

    private function saveOrder()
    {
        $command = $this->command;
        $data = $command->orderData;

        $evOrder = EVOrder::create([
            'customer_id' => $command->customerId,
            'job_id' => $command->jobId,
            'address' => $command->address,
            'delivery' => $command->productDeliveryOption,
            'company_id' => $this->scope->id(),
            'created_by' => \Auth::id(),
            'product_type' => $command->productType,
            'status_id' => EVOrder::ORDER_PRIMARY_STATUS,
            'sub_status_id' => EVOrder::ORDER_PRIMARY_STATUS,
            'claim_number' => ine($command->orderFields,'ClaimNumber') ? $command->orderFields['ClaimNumber'] : Null,
            'meta'         => $command->orderData,
        ]);
        return $evOrder;
    }

    private function createJobMeasurement($order)
    {
        $data['ev_report_id'] = $order->report_id;

        // save measurement ..
        $measurementRepo = \App::make(MeasurementRepository::class);
        $measurementRepo->save($order->job_id, $order->report_id, $values = [], \Auth::id(), $data);
    }
}
