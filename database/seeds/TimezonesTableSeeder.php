<?php
use Illuminate\Database\Seeder;
use App\Models\Timezone;

class TimezonesTableSeeder extends Seeder
{

    public function run()
    {
        Timezone::truncate();
        
        $timezones = [
            [
                'label'         =>  'Eastern Time',
                'name'          =>  'America/New_York',
                'country_id'    =>  1,
            ],
            [
                'label'         =>  'Central Time',
                'name'          =>  'America/Chicago',
                'country_id'    =>  1,
            ],
            [
                'label'         =>  'Mountain Time',
                'name'          =>  'America/Denver',
                'country_id'    =>  1,
            ],
            [
                'label'         =>  'Mountain Time (no DST)',
                'name'          =>  'America/Phoenix',
                'country_id'    =>  1,
            ],
            [
                'label'         =>  'Pacific Time',
                'name'          =>  'America/Los_Angeles',
                'country_id'    =>  1,
            ],
            [
                'label'         =>  'Alaska Time',
                'name'          =>  'America/Anchorage',
                'country_id'    =>  1,
            ],
            [
                'label'         =>  'Hawaii-Aleutian',
                'name'          =>  'America/Adak',
                'country_id'    =>  1,
            ],
            [
                'label'         =>  'Hawaii-Aleutian Time (no DST)',
                'name'          =>  'Pacific/Honolulu',
                'country_id'    =>  1,
            ],
            [
                'label'          =>  'Newfoundland Time',
                'name'           => 'America/St_Johns',
                'country_id'     =>  4,
            ],          [
                'label'          =>  'Atlantic Time - Nova Scotia (PEI)',
                'name'           => 'America/Halifax ',
                'country_id'     =>  4,
            ],
            [
                'label'          =>  'Atlantic Time - Nova Scotia (no DST)',
                'name'           => 'America/Glace_Bay',
                'country_id'     =>  4,
            ],
            [
                'label'          =>  'Atlantic Time - New Brunswick',
                'name'           => 'America/Moncton',
                'country_id'     =>  4,
            ],
            [
                'label'          =>  'Atlantic Time - Labrador ',
                'name'           => 'America/Goose_Bay',
                'country_id'     =>  4,
            ],
            [
                'label'          =>  'Atlantic Standard Time - Quebec',
                'name'           => 'America/Blanc-Sablon',
                'country_id'      =>  4,
            ],
            [
                'label'          =>  'Eastern Time - Ontario & Quebec',
                'name'           => 'America/Toronto',
                'country_id'     =>  4,
            ],
            [
                'label'          =>  'Eastern Time - Ontario & Quebec (no DST)',
                'name'           => 'America/Nipigon',
                'country_id'      =>  4,
            ],
            [
                'label'          =>  'Eastern Time - Thunder Bay, Ontario',
                'name'           => 'America/Thunder_Bay',
                'country_id'     =>  4,
            ],
            [
                'label'          =>  'Eastern Time - east Nunavut',
                'name'           => 'America/Iqaluit',
                'country_id'     =>  4,
            ],
            [
                'label'          =>  'Eastern Time - Pangnirtung, Nunavut',
                'name'           => 'America/Pangnirtung',
                'country_id'     =>  4,
            ],
            [
                'label'          =>  'Central Standard Time - Resolute, Nunavut',
                'name'           => 'America/Resolute',
                'country_id'      =>  4,
            ],
            [
                'label'          =>  'Eastern Standard Time - Atikokan, Ontario and Southampton',
                'name'           => 'America/Atikokan',
                'country_id'      =>  4,
            ],
            [
                'label'          =>  'Central Time - central Nunavut',
                'name'           => 'America/Rankin_Inlet',
                'country_id'     =>  4,
            ],
            [
                'label'          =>  'Central Time - Manitoba & west Ontario',
                'name'           => 'America/Winnipeg',
                'country_id'     =>  4,
            ],
            [
                'label'          =>  'Central Time - Rainy River & Fort Frances, Ontario',
                'name'           => 'America/Rainy_River',
                'country_id'      =>  4,
            ],
            [
                'label'          =>  'Central Standard Time - Saskatchewan',
                'name'           => 'America/Regina',
                'country_id'     =>  4,
            ],
            [
                'label'          =>  'Central Standard Time - Saskatchewan - midwest',
                'name'           => 'America/Swift_Current',
                'country_id'      =>  4,
            ],
            [
                'label'          =>  'Mountain Time - Alberta',
                'name'           => 'America/Edmonton',
                'country_id'      =>  4,
            ],
            [
                'label'          =>  'Mountain Time - west Nunavut',
                'name'           => 'America/Cambridge_Bay',
                'country_id'      =>  4,
            ],
            [
                'label'          =>  'Mountain Time - central Northwest',
                'name'           => 'America/Yellowknife',
                'country_id'     =>  4,
            ],
            [
                'label'          =>  'Mountain Time - west Northwest',
                'name'           => 'America/Inuvik',
                'country_id'     =>  4,
            ],
            [
                'label'          =>  'Mountain Standard Time - Creston, British Columbia',
                'name'           => 'America/Creston',
                'country_id'      =>  4,
            ],
            [
                'label'          =>  'Mountain Standard Time - Dawson Creek & Fort Saint John',
                'name'           => 'America/Dawson_Creek',
                'country_id'      =>  4,
            ],
            [
                'label'          =>  'Pacific Time - west British Columbia',
                'name'           => 'America/Vancouver',
                'country_id'      =>  4,
            ],
            [
                'label'          =>  'Pacific Time - south Yukon',
                'name'           => 'America/Whitehorse',
                'country_id'     =>  4,
            ],
            [
                'label'          =>  'Pacific Time - north Yukon',
                'name'           => 'America/Dawson',
                'country_id'     =>  4,
            ],
            [
                'label'          => 'Europe/London',
                'name'           => 'Europe/London',
                'country_id'     =>  5,
            ],
            [
                'label'          => 'Europe/Gibraltar',
                'name'           => 'Europe/Gibraltar',
                'country_id'     =>  5,
            ],
            [
                'label'          => 'Europe/Guernsey',
                'name'           => 'Europe/Guernsey',
                'country_id'     =>  5,
            ],
            [
                'label'          => 'Europe/Isle_of_Man',
                'name'           => 'Europe/Isle_of_Man',
                'country_id'     =>  5,
            ],
            [
                'label'          => 'Europe/Jersey',
                'name'           => 'Europe/Jersey',
                'country_id'     =>  5,
            ],
            [
                'name'           => 'Australia/Adelaide',
                'label'          => 'Australia/Adelaide',
                'country_id'     =>  3,
            ],
            [
                'name'           => 'Australia/Brisbane',
                'label'          => 'Australia/Brisbane',
                'country_id'     =>  3,
            ],
            [
                'name'           => 'Australia/Broken_Hill',
                'label'          => 'Australia/Broken_Hill',
                'country_id'     =>  3,
            ],
            [
                'name'           => 'Australia/Currie',
                'label'          => 'Australia/Currie',
                'country_id'     =>  3,
            ],
            [
                'name'           => 'Australia/Darwin',
                'label'          => 'Australia/Darwin',
                'country_id'     =>  3,
            ],
            [
                'name'           => 'Australia/Eucla',
                'label'          => 'Australia/Eucla',
                'country_id'     =>  3,
            ],
            [
                'name'           => 'Australia/Hobart',
                'label'          => 'Australia/Hobart',
                'country_id'     =>  3,
            ],
            [
                'name'           => 'Australia/Lindeman',
                'label'          => 'Australia/Lindeman',
                'country_id'     =>  3,
            ],
            [
                'name'           => 'Australia/Lord_Howe',
                'label'          => 'Australia/Lord_Howe',
                'country_id'     =>  3,
            ],
            [
                'name'           => 'Australia/Melbourne',
                'label'          => 'Australia/Melbourne',
                'country_id'     =>  3,
            ],
            [
                'name'           => 'Australia/Perth',
                'label'          => 'Australia/Perth',
                'country_id'     =>  3,
            ],
            [
                'name'           => 'Australia/Sydney',
                'label'          => 'Australia/Sydney',
                'country_id'     =>  3,
            ],
        ];
        
        foreach ($timezones as $timezone) {
            Timezone::create($timezone);
        }
    }
}
