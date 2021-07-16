<?php
namespace App\Services\QuickBookDesktop\QueueHandler\QBD\PaymentMethod;

use App\Models\QuickBookDesktopTask;
use App\Services\QuickBookDesktop\QueueHandler\BaseTaskHandler;
use App\Services\QuickBookDesktop\Entity\PaymentMethod as QBDPaymentMethod;
use App\Services\QuickBookDesktop\TaskManager\TaskRegistrar;
use App\Repositories\PaymentMethodsRepository;
use App\Services\QuickBookDesktop\CDC\PaymentMethod as CDCPaymentMethod;
use App\Models\PaymentMethod as PaymentMethodModal;

class ImportHandler extends BaseTaskHandler
{
    public function __construct(
        QBDPaymentMethod $qbdPaymentMethod,
        PaymentMethodsRepository $paymentMethodsRepo,
        CDCPaymentMethod $entity,
        TaskRegistrar $taskRegistrar
    ) {
        $this->qbdPaymentMethod = $qbdPaymentMethod;
        $this->entity = $entity;
        $this->paymentMethodsRepo = $paymentMethodsRepo;
        $this->taskRegistrar = $taskRegistrar;
    }

    function synch($task, $meta)
    {
        $enities = $this->entity->parse($meta['xml']);

        foreach ($enities as $paymentMethod) {

            $jpPaymentMethod = PaymentMethodModal::where('label', $paymentMethod['Name'])
                ->where('company_id', '0')
                ->first();

            //check for the default payment methods
            if ($jpPaymentMethod) {
                continue;
            }

            $jpPaymentMethod = $this->paymentMethodsRepo->getByQBDId($paymentMethod['ListID']);

            $taskmeta = [
                'action' => QuickBookDesktopTask::CREATE,
                'object' => QuickBookDesktopTask::PAYMENT_METHOD,
                'object_id' => $paymentMethod['ListID'],
                'priority' => QuickBookDesktopTask::PRIORITY_ADD_PAYMENTMETHOD
            ];

            if ($jpPaymentMethod) {

                if ($jpPaymentMethod->qb_desktop_sequence_number == $paymentMethod['EditSequence']) {
                    // Log::warning('PaymentMethod already updated:', [$jpPaymentMethod->id]);
                    continue;
                }

                $taskmeta['priority'] = QuickBookDesktopTask::PRIORITY_MOD_PAYMENTMETHOD;
                $taskmeta['action'] = QuickBookDesktopTask::UPDATE;
            }

            $this->taskRegistrar->addTask(QUICKBOOKS_IMPORT_PAYMENTMETHOD, $meta['user'], $taskmeta);
        }
    }
}