<?php namespace App\Handlers\Commands;

use App\Models\SMOrder;
use App\Repositories\EstimationsRepository;
use App\Repositories\MeasurementRepository;
use App\Services\Contexts\Context;
use App\Services\SkyMeasure\SkyMeasure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SMPlaceOrderCommandHandler
{

    protected $command;
    protected $scope;
    protected $service;
    protected $estimateRepo;

    public function __construct(Context $scope, SkyMeasure $service, EstimationsRepository $estimateRepo, MeasurementRepository $measurementRepo)
    {
        $this->scope = $scope;
        $this->service = $service;
        $this->estimateRepo = $estimateRepo;
        $this->measurementRepo = $measurementRepo;
    }

    /**
     * Handle the command.
     *
     * @param object $command
     * @return void
     */
    public function handle($command)
    {
        $this->command = $command;
        DB::beginTransaction();
        try {
            // save order data..
            $order = SMOrder::create([
                'company_id' => $this->scope->id(),
                'customer_id' => $this->command->customerId,
                'job_id' => $this->command->jobId,
                'details' => $command->orderData,
                'status' => SMOrder::IN_PROGRESS,
                'created_by' => \Auth::id(),
            ]);

            // place order..
            $orderId = $this->service->placeOrder($command->smToken, $command->orderData);
            // save sm order id..
            $order->order_id = $orderId;
            $order->save();


            //save measurement
            $data['sm_order_id'] = $orderId;
            $this->measurementRepo->save($command->jobId, $orderId, $values = [], \Auth::id(), $data);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
        DB::commit();

        // handle Evenet Herer..

        return $order;
    }
}
