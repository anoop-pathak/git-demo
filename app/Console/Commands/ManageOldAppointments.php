<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\AppointmentRecurring;
use Illuminate\Console\Command;

class ManageOldAppointments extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:manage_old_appointments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage Old Appointments';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->moveToRecuringTable();
        $count = Appointment::doesntHave('recurrings')->withTrashed()->count();
        echo 'Pending appointment count is ' . $count;
    }

    private function moveToRecuringTable()
    {
        $appointments = Appointment::doesntHave('recurrings')->withTrashed()
            ->whereCompanyId(55)
            ->orderBy('id', 'desc');

        $appointments->chunk(20, function ($appointments) {
            $data = [];
            foreach ($appointments as $appointment) {
                $data[] = [
                    'start_date_time' => $appointment->start_date_time,
                    'end_date_time' => $appointment->end_date_time,
                    'appointment_id' => $appointment->id,
                    'deleted_at' => $appointment->deleted_at,
                    'deleted_by' => $appointment->deleted_by,
                ];
            }

            AppointmentRecurring::insert($data);
        });
    }
}
