<?php
namespace App\Services\QuickBookDesktop\QueueHandler\QBD\Bill;

use App\Services\QuickBookDesktop\QueueHandler\BaseTaskHandler;
use Illuminate\Support\Facades\DB;
use App\Services\QuickBookDesktop\Entity\Bill as QBDBillEntity;

class DumpHandler extends BaseTaskHandler
{
    public function __construct(QBDBillEntity $entity)
    {
        $this->entity = $entity;
    }

    function synch($task, $meta)
    {
        $entities = $this->entity->dumpParse($meta['xml']);

        if(!$task->iterator_id && !empty($entities)){
            DB::table('qbd_bills')->where('company_id', getScopeId())->delete();
        }

        if(!empty($entities)){
            DB::Table('qbd_bills')->insert($entities);
        }
    }
}