<?php
namespace App\Services\QuickBookDesktop\QueueHandler\QBD\Invoice;

use App\Services\QuickBookDesktop\QueueHandler\BaseTaskHandler;
use App\Services\QuickBookDesktop\Entity\Invoice as QBDInvoice;
use Illuminate\Support\Facades\DB;

class DumpHandler extends BaseTaskHandler
{
    public function __construct(QBDInvoice $entity)
    {
        $this->entity = $entity;
    }

    function synch($task, $meta)
    {
        $entities = $this->entity->dumpParse($meta['xml']);

        if(!$task->iterator_id && !empty($entities)){
            DB::table('qbd_invoices')->where('company_id', getScopeId())->delete();
        }

        if(!empty($entities)){
            DB::Table('qbd_invoices')->insert($entities);
        }
    }
}