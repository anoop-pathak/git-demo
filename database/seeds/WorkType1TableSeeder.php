<?php

// Composer: "fzaninotto/faker": "v1.3.0"
use Illuminate\Database\Seeder;
use App\Models\JobType;

class WorkType1TableSeeder extends Seeder
{

    public function run()
    {
        
        $workTypes1 = [
            [
                'trade_id'   => 0,
                'name'       => 'Installation',
                'type'       => 1
            ],
            [
                'trade_id'   => 0,
                'name'       => 'Service / Maintenance / Warranty',
                'type'       => 1
            ],
            [
                'trade_id'   => 0,
                'name'       => 'Repair',
                'type'       => 1
            ],
            [
                'trade_id'   => 0,
                'name'       => JobType::INSURANCE_CLAIM,
                'type'       => 1
            ],
            [
                'trade_id'   => 0,
                'name'       => 'Inspection',
                'type'       => 1
            ],
        ];

        foreach ($workTypes1 as $workType1) {
            $jobType = JobType::firstOrNew($workType1);
            $jobType->save();
        }
    }
}
