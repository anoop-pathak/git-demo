<?php
namespace App\Services\QuickBookDesktop\QueueHandler\QBD\ReceivePayment;

use App\Services\QuickBookDesktop\QueueHandler\BaseTaskHandler;
use App\Services\QuickBookDesktop\Entity\ReceivePayment as QBDReceivePayment;
use Illuminate\Support\Facades\DB;

class DumpHandler extends BaseTaskHandler
{
    public function __construct(QBDReceivePayment $entity)
    {
        $this->entity = $entity;
    }

    function synch($task, $meta)
    {
        $entities = $this->entity->dumpParse($meta['xml']);

        if(!$task->iterator_id && !empty($entities)){
            DB::table('qbd_payments')->where('company_id', getScopeId())->delete();
        }

        if(!empty($entities)){
            DB::Table('qbd_payments')->insert($entities);
        }
    }
}