<?php

namespace App\Console\Commands;

use App\Models\Address;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class geocoding extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:geocoding';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Geocode the addresses';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // $totalLimit = config('jp.geocoding_usage_limit');
        // $limitSave = config('jp.geocoding_save_limit');
        // $limitUsed = TempLog::getGeocodingUsage();
        // $limit = $totalLimit - ($limitSave + $limitUsed);
        // if($limit <= 0) return;
        $addresses = Address::with('state', 'country')->where(function ($query) {
            $query->whereNull('lat')->orWhereNull('long');
        })->where('geocoding_error', false)
            ->where('company_id', '!=', 12)
            ->orderBy('id', 'asc');
        // ->limit($limit);
        $addresses->chunk(200, function ($addresses) {
            foreach ($addresses as $key => $address) {
                $this->attachGeoLocation($address);
            }
            // sleep(1);
        });
    }

    private function attachGeoLocation(Address $address)
    {
        try {
            $location = null;

            $fullAddress = $address->present()->fullAddress;

            if (!empty(trim($fullAddress))) {
                $location = geocode($fullAddress);
            }

            if (!$location) {
                $address->geocoding_error = true;
                $address->save();
                return false;
            }

            $address->lat = $location['lat'];
            $address->long = $location['lng'];
            $address->save();
        } catch (\Exception $e) {
            // No exception will be thrown here
            Log::warning('Address - Geocoder Error: ' . $e->getMessage());
        }
    }

    // /**
    //  * Get the console command arguments.
    //  *
    //  * @return array
    //  */
    // protected function getArguments()
    // {
    // 	return array(
    // 		array('example', InputArgument::REQUIRED, 'An example argument.'),
    // 	);
    // }

    // /**
    //  * Get the console command options.
    //  *
    //  * @return array
    //  */
    // protected function getOptions()
    // {
    // 	return array(
    // 		array('example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null),
    // 	);
    // }
}
