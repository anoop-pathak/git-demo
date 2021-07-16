<?php namespace App\Commands;

use App\Handlers\Commands\SMPlaceOrderCommandHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SMPlaceOrderCommand implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $input = [];
    public $orderData = [];
    public $customerId;
    public $jobId;
    public $smToken;

    public function __construct($input)
    {
        $smToken = $input['smToken'];
        $input = $input['input'];
        $this->input = $input;
        $this->smToken = $smToken;
        $this->customerId = $input['customer_id'];
        $this->jobId = $input['job_id'];

        $this->mapOrderData();
    }
    
    public function handle()
    {
        $commandHandler = \App::make(SMPlaceOrderCommandHandler::class);
        
        return $commandHandler->handle($this);
    }

    private function mapOrderData()
    {
        $orderFields = [
            'Address',
            'City',
            'State',
            'Zip',
            'Latitude',
            'Longitude',
            'ClaimNumber',
            'Comments',
            'HomeOwnerName',
            'Commercial',
            'AdditionalDeliveryOptions',
            'AdditionalEmail',
            'PromoCode',
            'DateOfLoss',
            'AdditionalFormatOptions',
        ];

        $this->orderData = $this->mapInputs($orderFields, $this->input);

        $this->orderData['Special'] = $this->mapSpecialFields();

        // set sourec Id..
        $this->orderData['OrderSource'] = config('skymeasure.source_id');// order source code for JP..

        // set report formats to order
        $defaultFormats = ["pdf, xml"];//, "csv", "roof"];
        $additionalFormats = [];
        if (isset($this->orderData['AdditionalFormatOptions'])) {
            $additionalFormats = $this->orderData['AdditionalFormatOptions'];
        }

        $this->orderData['AdditionalFormatOptions'] = arry_fu(array_merge($defaultFormats, $additionalFormats));

        // $this->orderData["ContractorName"] = "Contractor 1";
        // $this->orderData["Contractor Address"] = "123 Contractor St.";
        // $this->orderData["ContractorPhone"] = "(555) 555-5555";
        // $this->orderData["ContractorEmail"] = "ContractorUser@ContractorDomain.net";
    }

    private function mapInputs($map, $input = [])
    {

        $ret = [];

        if (empty($input)) {
            $input = $this->input;
        }

        foreach ($map as $key => $value) {
            if (is_numeric($key)) {
                $ret[$value] = isset($input[$value]) ? $input[$value] : null;
            } else {
                $ret[$key] = isset($input[$value]) ? $input[$value] : null;
            }
        }

        return $ret;
    }

    private function mapSpecialFields()
    {
        $special = [];

        $specialFieldData = ine($this->input, 'Special') ? $this->input['Special'] : [];

        $boolValues = [
            'IncludePitchedOnly',
            'IncludeFlatOnly',
            'IncludeUnderThePinOnly',
            'IncludeEntireBuilding',
        ];

        foreach ($boolValues as $key => $value) {
            $special[$value] = ine($specialFieldData, $value) ? (int)isTrue($specialFieldData[$value]) : 0;
        }

        if (ine($specialFieldData, 'PitchValue')) {
            $special['PitchValue'] = $specialFieldData['PitchValue'];
        }

        return $special;
    }
}
