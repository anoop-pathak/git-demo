<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class TempLog extends Model
{
    protected $fillable = ['company_id', 'key', 'value'];

    const GEOCODING_USAGE = 'geocoding_usage';

    protected function trackGecodingRequest()
    {
        $currentDate = Carbon::now()->toDateString();
        $log = self::where('key', self::GEOCODING_USAGE)->where('created_at', 'Like', '%' . $currentDate . '%')->first();
        if (!$log) {
            self::create([
                'key' => self::GEOCODING_USAGE,
                'value' => 1
            ]);
        } else {
            $log->value = (int)$log->value + 1;
            $log->save();
        }
    }

    protected function getGeocodingUsage()
    {
        $currentDate = Carbon::now()->toDateString();
        $log = self::where('key', self::GEOCODING_USAGE)->where('created_at', 'Like', '%' . $currentDate . '%')->first();
        if ($log) {
            return (int)$log->value;
        }
        return 0;
    }
}
