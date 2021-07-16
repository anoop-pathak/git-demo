<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TempImportCustomer extends Model
{
    protected $table = 'temp_import_customers';
    protected $fillable = ['company_id', 'data', 'is_valid', 'errors', 'duplicate', 'quickbook', 'is_commercial', 'quickbook_id'];

    protected $importRules = [
        'file' => 'required|mime_types:text/plain,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/csv,application/octet-stream'
    ];

    protected function getImportRules()
    {
        return $this->importRules;
    }


    public function setDataAttribute($value)
    {
        $value = (array)$value;
        $this->attributes['data'] = json_encode($value);
    }

    public function getDataAttribute($value)
    {
        return json_decode($value, true);
    }

    public function getErrorsAttribute($value)
    {
        return json_decode($value, true);
    }

    public function setErrorsAttribute($value)
    {
        $value = (array)$value;
        $this->attributes['errors'] = json_encode($value);
    }

    public function scopeValid($query)
    {
        return $query->whereIsValid(true)
            ->whereDuplicate(false);
    }

    public function scopeInvalid($query)
    {
        return $query->whereIsValid(false);
    }

    public function scopeDuplicate($query)
    {
        return $query->whereDuplicate(true)
            ->whereIsValid(true);
    }

    public function scopeQuickBook($query)
    {
        return $query->whereDuplicate(false)->whereQuickbook(true);
    }
}
