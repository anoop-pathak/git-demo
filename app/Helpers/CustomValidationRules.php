<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Request;
use Illuminate\Validation\Validator;
use Settings;

class CustomValidationRules extends Validator
{

    public function validateMimeTypes($attribute, $value, $parameters)
    {
        if (in_array('base64', $parameters) && Request::get('image_base_64')) {
            if (!Request::hasFile($attribute)) {
                return true;
            } else {
                return false;
            }
        }

        if (Request::hasFile($attribute)) {
            $mimeType = $value->getMimeType();
            if (in_array($mimeType, $parameters)) {
                return true;
            }
        }
    }

    public function validateValidEmailsArray($attribute, $emails, $parameters)
    {
        if (!is_array($emails) || empty(array_filter($emails))) {
            return true;
        }
        foreach ($emails as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return false;
            }
        }
        return true;
    }

    public function validateMultipleFilesMimes($attribute, $value, $parameters)
    {
        $files = (array)$value;
        foreach ($files as $file) {
            if (!is_file($file)) {
                return false;
            }

            $mimeType = $file->getMimeType();
            if (!in_array($mimeType, $parameters)) {
                return false;
            }
        }
        return true;
    }

    public function validateMultipleFilesMaxSize($attribute, $value, $parameters)
    {
        $files = (array)$value;
        foreach ($files as $file) {
            if (!is_file($file)) {
                return false;
            }
            $fileSize = $file->getSize() / 1000;
            if ($fileSize > $parameters[0]) {
                return false;
            }
        }
        return true;
    }

    protected function validatePastDateTimeCheck($attribute, $value, $parameters)
    {
        $date = new Carbon($value, \Settings::get('TIME_ZONE'));
        $now = Carbon::now(\Settings::get('TIME_ZONE'));
        // if date time is less than or equal to current time
        if ($date <= $now) {
            return false;
        }
        return true;
    }

    protected function validatePastDateCheck($attribute, $value, $parameters)
    {
        $date = new Carbon($value);
        $now = Carbon::now(\Settings::get('TIME_ZONE'))->toDateString();
        // if date is less than current time
        if ($date->toDateString() < $now) {
            return false;
        }
        return true;
    }

    protected function validateDateAfter($attribute, $value, $parameters)
    {
        $input = Request::all();

        $date1 = new Carbon($input['start_date_time']);
        $date2 = new Carbon($value);
        if ($date1->toDateTimeString() >= $date2->toDateTimeString()) {
            return false;
        }

        return true;
    }

    /**
     * Custom validation Greater than zero
     * @param  [type] $attribute  [field name]
     * @param  [type] $value      [value]
     * @param  [type] $parameters [extra param]
     * @return [type]             [true or false]
     */
    public function validateGreaterThanZero($attribute, $value, $parameters)
    {
        return ($value > 0);
    }

    public function validatePaymentGreaterThanOrEqualInvoiceAmount($attribute, $value, $parameters)
    {
        return ($value >= $parameters);
    }

    public function validateMaxArraySize($attribute, $value, $parameters)
    {
        return (sizeof((array)$value) <= (int)$parameters[0]);
    }

    /**
     * Current Date Check
     * @param  [type] $attribute  [description]
     * @param  [type] $value      [description]
     * @param  [type] $parameters [description]
     * @return [type]             [description]
     */
    protected function validateCurrentDateTimeCheck($attribute, $value, $parameters)
    {
        $date = new Carbon($value, Settings::get('TIME_ZONE'));
        $now = Carbon::now(\Settings::get('TIME_ZONE'));
        if ($date->toDateString() < $now->toDateString()) {
            return false;
        }

        return true;
    }

    /**
     * File Max Size In MB
     * @param  [type] $attribute  [description]
     * @param  [type] $value      [description]
     * @param  [type] $parameters [description]
     * @param  [type] $validator  [description]
     * @return [type]             [description]
     */
    protected function validateMaxMb($attribute, $value, $parameters, $validator)
    {
        if (!$value instanceof UploadedFile) {
            return false;
        }

        if(!$value->isValid()) {
            return false;
        }

        //get filesize() return file size in mbs
        $megabytes = $value->getSize() / 1024 / 1024;

        return $megabytes <= $parameters[0];
    }

    public function validateColorCode($attribute, $value)
    {
        if(preg_match("/^[#]([0-9A-Fa-f]{3}$)/", $value) || preg_match("/^[#]([0-9A-Fa-f]{6}$)/", $value)) {
            return true;
        }

        return false;
    }

    public function validateTenDigitAllow($attribute, $value)
    {
        if(preg_match("/^-?\d{0,10}(\.\d{1,3})?$/", $value)) {
            return true;
        }

        return false;
    }

    public function validateCustomerPhone($attribute, $value, $parameters) {
        $value = str_replace('-', '', $value);
        $value = preg_replace('/[^A-Za-z0-9\-]/', '', $value);

        return $this->validateDigitsBetween($attribute, $value, $parameters);
    }

    public function validateMaxPrimary($attribute, $value, $parameters) {
        $totalPrimary = 0;
        foreach ($value as $v) {
            $totalPrimary += ine($v, 'is_primary');
        }

        return ($totalPrimary <= (int)$parameters);
    }
}
