<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SRSConnectRequest extends FormRequest
{

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'account_number' => 'required',
            'invoice_number' => 'required',
            'billed_amount' => 'required_without:invoice_date',
            'invoice_date' => 'required_without:billed_amount'
        ];
    }
}
