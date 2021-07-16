<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AppointmentRequest extends FormRequest
{

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            // 'user_id'           =>  'required',
            'title' => 'required',
            'start_date_time'   =>  'required_without:full_day|required_if:full_day,0|date_format:Y-m-d H:i:s',
            'end_date_time'     =>  'required_without:full_day|required_if:full_day,0|date|after:start_date_time|date_format:Y-m-d H:i:s',
            'full_day'          =>  'boolean',
            'date'              =>  'required_if:full_day,1|date_format:Y-m-d',
            'location_type'     =>  'in:job,customer,other',
            'repeat'            =>  'in:daily,weekly,monthly,yearly',
            // 'occurence'         =>  'in:required',
            'until_date' => 'required_if:occurence,until_date',
            'by_day' => 'array',
            'reminders' => 'array|max:5',
            'attachments'       =>  'array'
        ];
    }
}
