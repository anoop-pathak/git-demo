<?php

namespace App\Handlers\Commands;

use App\Services\Appointments\AppointmentService;

class AppointmentCommandHandler
{

    /**
     *  Command Object
     * @var App\Appointments\AppointmentCommand
     */
    private $command;
    protected $service;

    public function __construct(AppointmentService $service)
    {
        $this->service = $service;
    }

    /**
     * Handle the command.
     *
     * @param object $command
     * @return void
     */
    public function handle($command)
    {
        $this->command = $command;
        $appointment = null;
        // DB::beginTransaction();
        try {
            if (ine($command->appointmentData, 'id')) {
                $appointmentData = $command->appointmentData;
                if ($this->command->onlyThis) {
                    $appointmentData['repeat'] = null;
                    $appointmentData['occurence'] = null;
                }
                $appointment = $this->service->update(
                    $appointmentData['id'],
                    $appointmentData,
                    $command->startDateTime,
                    $command->endDateTime,
                    $command->attendees,
                    $command->jobIds,
                    $command->invites,
                    $command->onlyThis,
                    $command->impactType,
                    $command->attachments,
                    $command->delete_attachments
                );
            } else {
                $appointment = $this->service->save(
                    $command->appointmentData,
                    $command->startDateTime,
                    $command->endDateTime,
                    $command->attendees,
                    $command->jobIds,
                    $command->invites,
                    $command->attachments
                );
            }
        } catch (\Exception $e) {
            // DB::rollback();
            throw $e;
        }
        // DB::commit();

        return $appointment;
    }
}
