<?php
use Illuminate\Database\Seeder;
use App\Models\Flag;

class FlagsTableSeeder extends Seeder
{

    public function run()
    {

        Flag::truncate();

        $flags = [
            [
                'id'    =>  1,
                'for'   => 'customer',
                'title' =>  'Reputation Motivated'
            ],
            [
                'id'    =>  2,
                'for'   => 'customer',
                'title' =>  'Spender'
            ],
            [
                'id'    =>  3,
                'for'   => 'customer',
                'title' =>  'Price Motivated'
            ],
            [
                'id'    =>  4,
                'for'   => 'customer',
                'title' =>  'Negotiator'
            ],
            [
                'id'    =>  5,
                'for'   => 'customer',
                'title' =>  'Researcher'
            ],
            [
                'id'    =>  6,
                'for'   => 'customer',
                'title' =>  'Quality Motivated'
            ],
            [
                'id'    =>  7,
                'for'   => 'customer',
                'title' => 'Hot Prospect'
            ],
            [
                'id'    =>  8,
                'for'   => 'customer',
                'title' =>  'Difficult'
            ],
            [
                'id'    =>  9,
                'for'   => 'job',
                'title' =>  'Complaint'
            ],
            [
                'id'    =>  10,
                'for'   => 'job',
                'title' =>  'Requires Permit'
            ],
            [
                'id'    =>  11,
                'for'   => 'job',
                'title' =>  'Special Requirement'
            ],
            [
                'id'    =>  12,
                'for'   => 'job',
                'title' =>  'Owes Money'
            ],
            [
                'id'    =>  13,
                'for'   => 'job',
                'title' =>  'Job Scheduled'
            ],
            [
                'id'    =>  14,
                'for'   => 'job',
                'title' =>  'Materials Ordered'
            ],
            [
                'id'    =>  15,
                'for'   => 'job',
                'title' => 'Job Awarded'
            ],
            [
                'id'    =>  16,
                'for'   => 'job',
                'title' => 'Work Completed'
            ],
            [
                'id'    =>  17,
                'for'   => 'job',
                'title' => 'ASAP / Emergency'
            ],
            [
                'id'    =>  18,
                'for'   => 'job',
                'title' => 'Warranty Claim'
            ],
            [
                'id'    =>  19,
                'for'   => 'job',
                'title' => 'Scheduling Request'
            ],
            [
                'id'    =>  20,
                'for'   => 'customer',
                'title' => 'Time Motivated'
            ],
            [
                'id'    =>  21,
                'for'   => 'customer',
                'title' => 'Impatient'
            ],
            [
                'id'    =>  22,
                'for'   => 'job',
                'title' => 'Deposit Required'
            ],
            [
                'id'    =>  23,
                'for'   => 'job',
                'title' => 'Tax Exempt'
            ],
            [
                'id'    =>  24,
                'for'   => 'customer',
                'title' => 'Previous Customer'
            ],
            [
                'id'    =>  25,
                'for'   => 'customer',
                'title' => 'Bad Lead'
            ],
            [
                'id'    =>  26,
                'for'   => 'customer',
                'title' => 'Not Closed'
            ],
        ];
        
        Flag::insert($flags);
    }
}
