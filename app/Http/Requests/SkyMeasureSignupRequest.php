<?php namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SkyMeasureSignupRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            "Username" => 'required|email',
            "Password" => 'required|min:6',
            "FirstName" => 'required',
            "LastName" => 'required',
            "CellPhone" => 'required',
            "CompanyName" => 'required',
            "CompanyAddress" => 'required',
            "CompanyCity" => 'required',
            "CompanyState" => 'required',
            "CompanyZip" => 'required',
            "CompanyPhone" => 'required',
            "BillingName" => 'required',
            "BillingAddress" => 'required',
            "BillingCity" => 'required',
            "BillingState" => 'required',
            "BillingZip" => 'required',
            "BillingPhone" => 'required',
            "CardNumber" => 'required',
            "CardExp" => 'required|date_format:my',
            "CardCode" => 'required',
            "CoveragePlus" => 'in:Yes,No,Ask',
            "PreferredContact" => 'in:Email,Office Phone,Cell Phone',
        ];
    }
}
