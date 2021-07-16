<?php
use Illuminate\Database\Seeder;

class JobsAppointmentTableSeeder extends Seeder
{

    public function run()
    {
        $appointments = Appointment::where('job_id', '!=', '0')->pluck('job_id', 'id');
        $data = [];
        foreach ($appointments as $appointmentId => $jobId) {
            $data[] = [
                'job_id'         => $jobId,
                'appointment_id' => $appointmentId
            ];
        }
        if (! empty($data)) {
            DB::table('job_appointment')->insert($data);
        }
    }
}
