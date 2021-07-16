<?php
namespace App\Services\QuickBookDesktop\QueueHandler\QBD\CreditMemo;

use App\Services\QuickBookDesktop\QueueHandler\BaseTaskHandler;
use Illuminate\Support\Facades\DB;
use App\Services\QuickBookDesktop\Entity\CreditMemo as QBDCreditMemo;

class DumpHandler extends BaseTaskHandler
{
    public function __construct(QBDCreditMemo $entity)
    {
        $this->entity = $entity;
    }

    function synch($task, $meta)
    {
        $entities = $this->entity->dumpParse($meta['xml']);

        if(!$task->iterator_id && !empty($entities)){
            DB::table('qbd_credit_memo')->where('company_id', getScopeId())->delete();
        }

        if(!empty($entities)){
            DB::Table('qbd_credit_memo')->insert($entities);
        }
    }
}