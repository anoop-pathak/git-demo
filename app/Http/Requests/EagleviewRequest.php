<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EagleviewRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'Address'                       => 'required',
            'City'                          => 'required',
            'State'                         => 'required',
            'Zip'                           => 'required',
            'PrimaryProductId'              => 'required|integer',
            'DeliveryProductId'             => 'required|integer',
            'MeasurementInstructionType'    => 'required|integer',
            'ChangesInLast4Years'           => 'required|boolean',
            'ProductDeliveryOptionName'     => 'required',
            'ProductTypeName'               => 'required',
            'customer_id'                   => 'required',
            'job_id'                        => 'required',
            'DateOfLoss'                    => 'date'
        ];
    }
}
