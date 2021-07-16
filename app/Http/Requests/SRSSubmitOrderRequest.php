<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SRSSubmitOrderRequest extends FormRequest
{

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'material_list_id' => 'required',
            'ship_to_address.city' => 'required',
            'ship_to_address.state' => 'required|max:2',
            'ship_to_address.zip_code' => 'required',
            'ship_to_address.address_line1' => 'required',
            'bill_to.city' => 'required',
            'bill_to.state' => 'required|max:2',
            'bill_to.zip_code' => 'required',
            'po_details.expected_delivery_date' => 'required',
            'customer_contact.name' => 'required',
            'customer_contact.email' => 'required',
            'customer_contact.phone' => 'required',
            'customer_contact.address.city' => 'required',
            'customer_contact.address.state' => 'required|max:2',
            'customer_contact.address.zip_code' => 'required',
            'po_details.shipping_method' => 'in:Ground drop,Willcall',
        ];
    }
}
