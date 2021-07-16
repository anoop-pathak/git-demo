<?php
use Illuminate\Database\Seeder;
use App\Models\EVSubStatus;

class EVSubStatusTableSeeder extends Seeder
{
    
    public function run()
    {

        EVSubStatus::truncate();

        $status = [
            [
                'id'        => 1,
                'status_id' => 1,
                'name'      => 'Customer'
            ],
            [
                'id'        => 6,
                'status_id' => 2,
                'name'      => 'Process Started'
            ],
            [
                'id'        => 26,
                'status_id' => 2,
                'name'      => 'Ready To Send'
            ],
            [
                'id'        => 5,
                'status_id' => 2,
                'name'      => 'Under Review'
            ],
            [
                'id'        => 29,
                'status_id' => 2,
                'name'      => 'AddressVerified'
            ],
            [
                'id'        => 27,
                "status_id" => 3,
                "name"      => "CreditCard Failure"
            ],
            [
                'id'        => 21,
                "status_id" => 3,
                "name"      => "Customer Response"
            ],
            [
                'id'        => 8,
                "status_id" => 3,
                "name"      => "Need To ID"
            ],
            [
                'id'        => 14,
                "status_id" => 3,
                "name"      => "Report Type Change"
            ],
            [
                'id'        => 7,
                "status_id" => 3,
                "name"      => "Site Map"
            ],
            [
                'id'        => 10,
                "status_id" => 4,
                "name"      => "Canceled By Client"
            ],
            [
                'id'        => 18,
                "status_id" => 4,
                "name"      => "Card Rejected"
            ],
            [
                'id'        => 9,
                "status_id" => 4,
                "name"      => "Duplicate"
            ],
            [
                'id'        => 12,
                "status_id" => 4,
                "name"      => "No ID"
            ],
            [
                'id'        => 15,
                "status_id" => 4,
                "name"      => "No Images"
            ],
            [
                'id'        => 16,
                "status_id" => 4,
                "name"      => "Other"
            ],
            [
                'id'        => 11,
                "status_id" => 4,
                "name"      => "Poor Images"
            ],
            [
                'id'        => 14,
                "status_id" => 4,
                "name"      => "Report Type Change"
            ],
            [
                'id'        => 17,
                "status_id" => 4,
                "name"      => "System"
            ],
            [
                'id'        => 24,
                "status_id" => 4,
                "name"      => "Wrong House"
            ],
            [
                'id'        => 19,
                "status_id" => 5,
                "name"      => "Sent"
            ],
            [
                'id'        => 20,
                "status_id" => 5,
                "name"      => "sent"
            ]
        ];
        EVSubStatus::insert($status);
    }
}
