<?php
use Illuminate\Database\Seeder;

class JobWorkTypesTableSeeder extends Seeder
{

    public function run()
    {
        $jobs = Job::where('job_type_id', '!=', '0')->pluck('job_type_id', 'id');
        $data = [];
        foreach ($jobs as $jobId => $jobTypeId) {
            $data[] = [
                'job_id' => $jobId,
                'job_type_id' => $jobTypeId
            ];
        }
        if (!empty($data)) {
            DB::table('job_work_types')->insert($data);
        }
    }
}
