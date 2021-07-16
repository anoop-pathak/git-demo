<?php
namespace App\Services\QuickBookDesktop;

use Exception;
use QuickBooks_WebConnector_Queue;
use Illuminate\Support\Facades\Log;
use App\Models\QuickBookDesktopTask;
use App\Services\QuickBookDesktop\QBDesktopUtilities;

class TaskScheduler
{
    public function addTask($qbAction, $user, $meta = [])
    {
        try {

            $taskMeta = [
                'qb_action' => $qbAction,
                'user' => $user,
                'priority' => null,
                'action' => $meta['action'],
                'object' => $meta['object'],
                'ident' => $this->GUIDv4(),
            ];

            if (isset($meta['origin'])) {
                $taskMeta['origin'] = $meta['origin'];
            }

            if (isset($meta['priority'])) {
                $taskMeta['priority'] = $meta['priority'];
            }

            if (ine($meta, 'object_id')) {

                if($meta['action'] != QuickBookDesktopTask::DUMP_UPDATE){
                    $taskMeta['ident'] = $meta['object_id'];
                }

                $taskMeta['object_id'] = $meta['object_id'];
            }

            if (ine($meta, 'qb_desktop_id')) {
                $taskMeta['qb_desktop_id'] = $meta['qb_desktop_id'];
            }

            if (ine($meta, 'qb_customer_id')) {
                $taskMeta['qb_customer_id'] = $meta['qb_customer_id'];
            }

            if (ine($meta, 'customer_id')) {
                $taskMeta['customer_id'] = $meta['customer_id'];
            }

            if (ine($meta, 'qb_job_id')) {
                $taskMeta['qb_job_id'] = $meta['qb_job_id'];
            }

            if (ine($meta, 'job_id')) {
                $taskMeta['job_id'] = $meta['job_id'];
            }

            if (ine($meta, 'batch_id')) {
                $taskMeta['batch_id'] = $meta['batch_id'];
            }

            if (ine($meta, 'group_id')) {
                $taskMeta['group_id'] = $meta['group_id'];
            }

            if (ine($meta, 'created_source')) {
                $taskMeta['created_source'] = $meta['created_source'];
            }

            if (ine($meta, 'object_last_updated')) {
                $taskMeta['object_last_updated'] = $meta['object_last_updated'];
            }

            if (ine($meta, 'object_last_updated')) {
                $taskMeta['object_last_updated'] = $meta['object_last_updated'];
            }

            if (ine($meta, 'paginate')) {
                $taskMeta['paginate'] = $meta['paginate'];
            }

            if (ine($meta, 'iterator_id')) {
                $taskMeta['iterator_id'] = $meta['iterator_id'];
            }

            // merge and relace any key exists in the extra with new keys
            $taskMeta = $taskMeta + $meta;

            if (!in_array($taskMeta['action'], [
                QuickBookDesktopTask::IMPORT,
                QuickBookDesktopTask::DUMP,
                QuickBookDesktopTask::SYNC_ALL,
            ])) {

                if (!ine($meta, 'object_id')) {

                    throw new Exception("Object id is missing!");
                }
            }

            $task = QuickBookDesktopTask::where('qb_username', $user)
                ->where('action', $meta['action'])
                ->whereNotIn('status', [QuickBookDesktopTask::STATUS_ERROR, QuickBookDesktopTask::STATUS_SUCCESS]);

            $task->where('object', $meta['object']);

            if (ine($meta, 'object_id')) {
                $task->where('object_id', $meta['object_id']);
            }

            if (ine($meta, 'object_last_updated')) {
                $task->where('object_last_updated', $meta['object_last_updated']);
            }

            if(ine($meta, 'iterator_id')) {
                $task->where('object', '!=', $meta['object']);
                $task->where('iterator_id', $meta['iterator_id']);
            }

            $task = $task->first();

            if ($task) {
                Log::warning("QBD: task already exists", [$task->quickbooks_queue_id]);
                return $task;
            }


            if ($taskMeta['action'] == QuickBookDesktopTask::UPDATE && ine($meta, 'object_last_updated')) {

                $task = QuickBookDesktopTask::where('qb_username', $user)
                    ->where('action', $meta['action'])
                    ->where('object', $meta['object'])
                    ->where('object_last_updated', $meta['object_last_updated'])
                    ->where('status', QuickBookDesktopTask::STATUS_SUCCESS)
                    ->first();

                if ($task) {
                    Log::warning("Object already updated", [$task->object]);
                    return;
                }
            }

            $queue = new QuickBooks_WebConnector_Queue(QBDesktopUtilities::dsn(), $user);

            $qbdTask = $queue->enqueue(
                $qbAction,
                $taskMeta['ident'],
                $taskMeta['priority'],
                $taskMeta,
                $qbxml = null,
                $replace = false
            );

            if($qbdTask) {

                $task = QuickBookDesktopTask::where('qb_username', $user)
                    ->where('qb_action', $qbAction)
                    ->where('ident', $taskMeta['ident'])
                    ->where('qb_status', QuickBookDesktopTask::STATUS_QUEUED)
                    ->first();

                $task->object = $meta['object'];
                $task->action = $meta['action'];

                if (ine($meta, 'origin')) {
                    $task->origin = $meta['origin'];
                }

                if (ine($meta, 'object_last_updated')) {
                    $task->object_last_updated = $meta['object_last_updated'];
                }

                if (ine($meta, 'object_id')) {
                    $task->object_id = $meta['object_id'];
                }

                if (ine($meta, 'iterator_id')) {
                    $task->iterator_id = $meta['iterator_id'];
                }

                if (ine($meta, 'group_id')) {
                    $task->group_id = $meta['group_id'];
                }

                if (ine($meta, 'created_source')) {
                    $task->created_source = $meta['created_source'];
                }

                $task->status = QuickBookDesktopTask::STATUS_QUEUED;

                $task->save();

                return $task;
            }

            return false;

        } catch (Exception $e) {
            Log::info('Add Task Error: ', [$e->getMessage()]);
        }
    }

    public function addRecurringTask($qbAction, $user, $meta = [])
    {
        try {

            $taskMeta = [
                'qb_action' => $qbAction,
                'user' => $user,
                'priority' => null,
                'action' => $meta['action'],
                'object' => $meta['object'],
                'ident' => $this->GUIDv4(),
            ];

            if (isset($meta['priority'])) {

                $taskMeta['priority'] = $meta['priority'];
            }

            if (ine($meta, 'object_id')) {

                $taskMeta['object_id'] = $meta['object_id'];
            }

            if (ine($meta, 'qb_desktop_id')) {

                $taskMeta['qb_desktop_id'] = $meta['qb_desktop_id'];
            }

            if (ine($meta, 'object_last_updated')) {

                $taskMeta['object_last_updated'] = $meta['object_last_updated'];
            }

            if ($taskMeta['action'] != QuickBookDesktopTask::IMPORT) {

                if (!ine($meta, 'object_id')) {

                    throw new Exception("Object id is missing!");
                }
            }

            $task = QuickBookDesktopTask::where('qb_username', $user)
                ->where('action', $meta['action'])
                ->whereNotIn('status', [QuickBookDesktopTask::STATUS_ERROR, QuickBookDesktopTask::STATUS_SUCCESS]);

            $task->where('object', $meta['object']);

            if (ine($meta, 'object_id')) {
                $task->where('object_id', $meta['object_id']);
            }

            if (ine($meta, 'object_last_updated')) {
                $task->where('object_last_updated', $meta['object_last_updated']);
            }

            if (ine($meta, 'iterator_id')) {
                $task->where('iterator_id', $meta['iterator_id']);
            }

            $task = $task->first();

            if ($task) {
                Log::warning("QBD: task already exists", [$task->quickbooks_queue_id]);
                return $task;
            }

            $queue = new QuickBooks_WebConnector_Queue(QBDesktopUtilities::dsn(), $user);

            $interval = '1 minutes';

            $qbdTask = $queue->recurring($interval, $qbAction, $taskMeta['ident'], $taskMeta['priority'], $taskMeta);

            if ($qbdTask) {

                $task = QuickBookDesktopTask::where('qb_username', $user)
                    ->where('ident', $taskMeta['ident'])
                    ->first();

                $task->object = $meta['object'];
                $task->action = $meta['action'];
                $task->origin = 1;

                if (ine($meta, 'object_last_updated')) {
                    $task->object_last_updated = $meta['object_last_updated'];
                }

                if (ine($meta, 'object_id')) {
                    $task->object_id = $meta['object_id'];
                }

                if (ine($meta, 'iterator_id')) {
                    $task->iterator_id = $meta['iterator_id'];
                }

                $task->status = QuickBookDesktopTask::STATUS_QUEUED;

                $task->save();

                return $task;
            }

            return false;
        } catch (Exception $e) {

            Log::debug($e);
        }
    }

    /**
     * Returns a GUIDv4 string
     *
     * Uses the best cryptographically secure method
     * for all supported pltforms with fallback to an older,
     * less secure version.
     * https://www.php.net/manual/en/function.com-create-guid.php
     *
     * @param bool $trim
     * @return string
     */
    function GUIDv4($trim = true)
    {
        // Windows
        if (function_exists('com_create_guid') === true) {
            if ($trim === true)
                return trim(com_create_guid(), '{}');
            else
                return com_create_guid();
        }

        // OSX/Linux
        if (function_exists('openssl_random_pseudo_bytes') === true) {
            $data = openssl_random_pseudo_bytes(16);
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40);    // set version to 0100
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80);    // set bits 6-7 to 10
            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        }

        // Fallback (PHP 4.2+)
        mt_srand((float) microtime() * 10000);
        $charid = strtolower(md5(uniqid(rand(), true)));
        $hyphen = chr(45);                  // "-"
        $lbrace = $trim ? "" : chr(123);    // "{"
        $rbrace = $trim ? "" : chr(125);    // "}"
        $guidv4 = $lbrace .
            substr($charid,  0,  8) . $hyphen .
            substr($charid,  8,  4) . $hyphen .
            substr($charid, 12,  4) . $hyphen .
            substr($charid, 16,  4) . $hyphen .
            substr($charid, 20, 12) .
            $rbrace;
        return $guidv4;
    }

    function addTaxCodeTask($qbdId, QuickBookDesktopTask $task)
    {
        $taskMeta = [
            'action' => QuickBookDesktopTask::CREATE,
            'object' => QuickBookDesktopTask::SALES_TAX_CODE,
            'object_id' => $qbdId,
            'priority' => QuickBookDesktopTask::PRIORITY_ADD_SALESTAXCODE,
            'origin' => QuickBookDesktopTask::ORIGIN_QBD
        ];

        $parentTask = $this->addTask(QUICKBOOKS_IMPORT_SALESTAXCODE, $task->qb_username, $taskMeta);

        $task->setParentTask($parentTask);
    }


    function addJobTask($qbdId, QuickBookDesktopTask $task)
    {
        $taskMeta = [
            'action' => QuickBookDesktopTask::CREATE,
            'object' => QuickBookDesktopTask::JOB,
            'object_id' => $qbdId,
            'priority' => QuickBookDesktopTask::PRIORITY_ADD_JOB,
            'origin' => QuickBookDesktopTask::ORIGIN_QBD
        ];

        $parentTask = $this->addTask(QUICKBOOKS_IMPORT_CUSTOMER, $task->qb_username, $taskMeta);

        $task->setParentTask($parentTask);
    }

    function addAccountTask($qbdId, QuickBookDesktopTask $task)
    {
        $taskMeta = [
            'action' => QuickBookDesktopTask::CREATE,
            'object' => QuickBookDesktopTask::ACCOUNT,
            'object_id' => $qbdId,
            'priority' => QuickBookDesktopTask::PRIORITY_ADD_ACCOUNT,
            'origin' => QuickBookDesktopTask::ORIGIN_QBD
        ];

        $parentTask = $this->addTask(QUICKBOOKS_IMPORT_ACCOUNT, $task->qb_username, $taskMeta);

        $task->setParentTask($parentTask);
    }

    function addItemTaxTask($qbdId, QuickBookDesktopTask $task)
    {
        $taskMeta = [
            'action' => QuickBookDesktopTask::CREATE,
            'object' => QuickBookDesktopTask::ITEM_SALES_TAX,
            'object_id' => $qbdId,
            'priority' => QuickBookDesktopTask::PRIORITY_ADD_SALESTAXITEM,
            'origin' => QuickBookDesktopTask::ORIGIN_QBD
        ];

        $parentTask = $this->addTask(QUICKBOOKS_IMPORT_SALESTAXITEM, $task->qb_username, $taskMeta);

        $task->setParentTask($parentTask);
    }

    function addPaymentMethodTask($qbdId, QuickBookDesktopTask $task)
    {
        $taskMeta = [
            'action' => QuickBookDesktopTask::CREATE,
            'object' => QuickBookDesktopTask::PAYMENT_METHOD,
            'object_id' => $qbdId,
            'priority' => QuickBookDesktopTask::PRIORITY_ADD_PAYMENTMETHOD,
            'origin' => QuickBookDesktopTask::ORIGIN_QBD
        ];

        $parentTask = $this->addTask(QUICKBOOKS_IMPORT_PAYMENTMETHOD, $task->qb_username, $taskMeta);

        $task->setParentTask($parentTask);
    }

    function addInvoiceTask($action, $qbdId, QuickBookDesktopTask $child = null, $userName = null)
    {
        if ($child) {
            $userName = $child->qb_username;
        }

        $priority = QuickBookDesktopTask::PRIORITY_ADD_INVOICE;

        if ($action == QuickBookDesktopTask::UPDATE) {
            $priority = QuickBookDesktopTask::PRIORITY_MOD_INVOICE;
        }

        $taskMeta = [
            'action' => $action,
            'object' => QuickBookDesktopTask::INVOICE,
            'object_id' => $qbdId,
            'priority' => $priority,
            'origin' => QuickBookDesktopTask::ORIGIN_QBD,
            'created_source' => QuickBookDesktopTask::SYSTEM_EVENT,
        ];

        $parentTask = $this->addTask(QUICKBOOKS_IMPORT_INVOICE, $userName, $taskMeta);

        if($child) {
            $child->setParentTask($parentTask);
        }

        return $parentTask;
    }

    function addCreditMemoTask($qbdId, QuickBookDesktopTask $child = null, $userName = null)
    {
        if ($child) {
            $userName = $child->qb_username;
        }

        $taskMeta = [
            'action' => QuickBookDesktopTask::CREATE,
            'object' => QuickBookDesktopTask::CREDIT_MEMO,
            'object_id' => $qbdId,
            'priority' => QuickBookDesktopTask::PRIORITY_ADD_CREDITMEMO,
            'origin' => QuickBookDesktopTask::ORIGIN_QBD,
            'created_source' => QuickBookDesktopTask::SYSTEM_EVENT,
        ];

        $parentTask = $this->addTask(QUICKBOOKS_IMPORT_CREDITMEMO, $userName, $taskMeta);

        if ($child) {
            $child->setParentTask($parentTask);
        }

        return $parentTask;
    }

    function addPaymentTask($action, $qbdId, QuickBookDesktopTask $child = null, $userName = null)
    {
        if ($child) {
            $userName = $child->qb_username;
        }

        $priority = QuickBookDesktopTask::PRIORITY_ADD_RECEIVEPAYMENT;

        if($action == QuickBookDesktopTask::UPDATE) {
            $priority = QuickBookDesktopTask::PRIORITY_MOD_RECEIVEPAYMENT;
        }

        $taskMeta = [
            'action' => $action,
            'object' => QuickBookDesktopTask::PAYMENT,
            'object_id' => $qbdId,
            'priority' => $priority,
            'origin' => QuickBookDesktopTask::ORIGIN_QBD,
            'created_source' => QuickBookDesktopTask::SYSTEM_EVENT,
        ];

        $parentTask = $this->addTask(QUICKBOOKS_IMPORT_RECEIVEPAYMENT, $userName, $taskMeta);

        if ($child) {
            $child->setParentTask($parentTask);
        }

        return $parentTask;
    }

    function addJpAccountTask($action, $id, QuickBookDesktopTask $child = null, $userName = null, $extra = [])
    {
        if ($child) {
            $userName = $child->qb_username;
        }

        if ($action == QuickBookDesktopTask::QUERY) {
            $qbAction = QUICKBOOKS_QUERY_ACCOUNT;
            $priority = QBDesktopUtilities::QB_QUERY_ACCOUNT_PRIORITY;
        }

        if ($action == QuickBookDesktopTask::CREATE) {
            $qbAction = QUICKBOOKS_ADD_ACCOUNT;
            $priority = QBDesktopUtilities::QB_ADD_ACCOUNT_PRIORITY;
        }

        if ($action == QuickBookDesktopTask::UPDATE) {
            $qbAction = QUICKBOOKS_MOD_ACCOUNT;
            $priority = QBDesktopUtilities::QB_ADD_ACCOUNT_PRIORITY - 1;
        }

        if ($action == QuickBookDesktopTask::DELETE) {
            $qbAction = QUICKBOOKS_DERIVE_ACCOUNT;
            $priority = QBDesktopUtilities::QB_ADD_ACCOUNT_PRIORITY - 2;
        }

        $taskMeta = [
            'action' => $action,
            'object' => QuickBookDesktopTask::ACCOUNT,
            'object_id' => $id,
            'priority' => $priority,
            'origin' => QuickBookDesktopTask::ORIGIN_JP,
            'created_source' => QuickBookDesktopTask::SYSTEM_EVENT,
        ];

        if($extra) {
            // merge and relace any key exists in the extra with new keys
            $taskMeta = $taskMeta + $extra;
        }

        $parentTask = $this->addTask($qbAction, $userName, $taskMeta);

        if ($child) {
            $child->setParentTask($parentTask);
        }

        return $parentTask;
    }

    function addJpVendorTask($action, $id, QuickBookDesktopTask $child = null, $userName = null, $extra = [])
    {
        if ($child) {
            $userName = $child->qb_username;
        }

        if ($action == QuickBookDesktopTask::QUERY) {
            $qbAction = QUICKBOOKS_QUERY_VENDOR;
            $priority = QBDesktopUtilities::QB_QUERY_VENDOR_PRIORITY;
        }

        if ($action == QuickBookDesktopTask::CREATE) {
            $qbAction = QUICKBOOKS_ADD_VENDOR;
            $priority = QBDesktopUtilities::QB_ADD_VENDOR_PRIORITY;
        }

        if ($action == QuickBookDesktopTask::UPDATE) {
            $qbAction = QUICKBOOKS_MOD_VENDOR;
            $priority = QBDesktopUtilities::QB_ADD_VENDOR_PRIORITY - 1;
        }

        if ($action == QuickBookDesktopTask::DELETE) {
            $qbAction = QUICKBOOKS_DERIVE_VENDOR;
            $priority = QBDesktopUtilities::QB_ADD_VENDOR_PRIORITY - 2;
        }

        $taskMeta = [
            'action' => $action,
            'object' => QuickBookDesktopTask::VENDOR,
            'object_id' => $id,
            'priority' => $priority,
            'origin' => QuickBookDesktopTask::ORIGIN_JP,
            'created_source' => QuickBookDesktopTask::SYSTEM_EVENT,
        ];

        if ($extra) {
            // merge and relace any key exists in the extra with new keys
            $taskMeta = $taskMeta + $extra;
        }

        $parentTask = $this->addTask($qbAction, $userName, $taskMeta);

        if ($child) {
            $child->setParentTask($parentTask);
        }

        return $parentTask;
    }

    function addJpCustomerTask($action, $id, QuickBookDesktopTask $child = null, $userName = null, $extra = [])
    {
        if ($child) {
            $userName = $child->qb_username;
        }

        if ($action == QuickBookDesktopTask::QUERY) {
            $qbAction = QUICKBOOKS_QUERY_CUSTOMER;
            $priority = QBDesktopUtilities::QB_QUERY_CUSTOMER_PRIORITY;
        }

        if ($action == QuickBookDesktopTask::CREATE) {
            $qbAction = QUICKBOOKS_ADD_CUSTOMER;
            $priority = QBDesktopUtilities::QB_ADD_CUSTOMER_PRIORITY;
        }

        if ($action == QuickBookDesktopTask::UPDATE) {
            $qbAction = QUICKBOOKS_MOD_CUSTOMER;
            $priority = QBDesktopUtilities::QB_ADD_CUSTOMER_PRIORITY - 1;
        }

        $taskMeta = [
            'action' => $action,
            'object' => QuickBookDesktopTask::CUSTOMER,
            'object_id' => $id,
            'priority' => $priority,
            'origin' => QuickBookDesktopTask::ORIGIN_JP,
            'created_source' => QuickBookDesktopTask::SYSTEM_EVENT,
        ];

        if ($extra) {
            // merge and relace any key exists in the extra with new keys
            $taskMeta = $taskMeta + $extra;
        }

        $parentTask = $this->addTask($qbAction, $userName, $taskMeta);

        if ($child) {
            $child->setParentTask($parentTask);
        }

        return $parentTask;
    }

    function addJpJobTask($action, $id, QuickBookDesktopTask $child = null, $userName = null, $extra = [])
    {
        if ($child) {
            $userName = $child->qb_username;
        }

        if ($action == QuickBookDesktopTask::QUERY) {
            $qbAction = QUICKBOOKS_QUERY_JOB;
            $priority = QBDesktopUtilities::QB_QUERY_JOB_PRIORITY;
        }

        if ($action == QuickBookDesktopTask::CREATE) {
            $qbAction = QUICKBOOKS_ADD_JOB;
            $priority = QBDesktopUtilities::QB_ADD_JOB_PRIORITY;
        }

        if ($action == QuickBookDesktopTask::UPDATE) {
            $qbAction = QUICKBOOKS_MOD_JOB;
            $priority = QBDesktopUtilities::QB_ADD_JOB_PRIORITY - 1;
        }

        $taskMeta = [
            'action' => $action,
            'object' => QuickBookDesktopTask::JOB,
            'object_id' => $id,
            'priority' => $priority,
            'origin' => QuickBookDesktopTask::ORIGIN_JP,
            'created_source' => QuickBookDesktopTask::SYSTEM_EVENT,
        ];

        if ($extra) {
            // merge and relace any key exists in the extra with new keys
            $taskMeta = $taskMeta + $extra;
        }

        $parentTask = $this->addTask($qbAction, $userName, $taskMeta);

        if ($child) {
            $child->setParentTask($parentTask);
        }

        return $parentTask;
    }

    function addJpInvoiceTask($action, $id, QuickBookDesktopTask $child = null, $userName = null, $extra = [])
    {
        if ($child) {
            $userName = $child->qb_username;
        }

        if ($action == QuickBookDesktopTask::QUERY) {
            $qbAction = QUICKBOOKS_QUERY_INVOICE;
            $priority = QBDesktopUtilities::QB_QUERY_INVOICE_PRIORITY;
        }

        if ($action == QuickBookDesktopTask::CREATE) {
            $qbAction = QUICKBOOKS_ADD_INVOICE;
            $priority = QBDesktopUtilities::QB_ADD_INVOICE_PRIORITY;
        }

        if ($action == QuickBookDesktopTask::UPDATE) {
            $qbAction = QUICKBOOKS_MOD_INVOICE;
            $priority = QBDesktopUtilities::QB_ADD_INVOICE_PRIORITY - 1;
        }

        if ($action == QuickBookDesktopTask::DELETE) {
            $qbAction = QUICKBOOKS_DERIVE_INVOICE;
            $priority = QBDesktopUtilities::QB_ADD_INVOICE_PRIORITY - 2;
        }

        $taskMeta = [
            'action' => $action,
            'object' => QuickBookDesktopTask::INVOICE,
            'object_id' => $id,
            'priority' => $priority,
            'origin' => QuickBookDesktopTask::ORIGIN_JP,
            'created_source' => QuickBookDesktopTask::SYSTEM_EVENT,
        ];

        if ($extra) {
            // merge and relace any key exists in the extra with new keys
            $taskMeta = $taskMeta + $extra;
        }

        $parentTask = $this->addTask($qbAction, $userName, $taskMeta);

        if ($child) {
            $child->setParentTask($parentTask);
        }

        return $parentTask;
    }

    function addJpCreditMemoTask($action, $id, QuickBookDesktopTask $child = null, $userName = null, $extra = [])
    {
        if ($child) {
            $userName = $child->qb_username;
        }

        if ($action == QuickBookDesktopTask::QUERY) {
            $qbAction = QUICKBOOKS_QUERY_CREDITMEMO;
            $priority = QBDesktopUtilities::QB_QUERY_CREDITMEMO_PRIORITY;
        }

        if ($action == QuickBookDesktopTask::CREATE) {
            $qbAction = QUICKBOOKS_ADD_CREDITMEMO;
            $priority = QBDesktopUtilities::QB_ADD_CREDITMEMO_PRIORITY;
        }

        if ($action == QuickBookDesktopTask::UPDATE) {
            $qbAction = QUICKBOOKS_MOD_CREDITMEMO;
            $priority = QBDesktopUtilities::QB_ADD_CREDITMEMO_PRIORITY - 1;
        }

        if ($action == QuickBookDesktopTask::DELETE) {
            $qbAction = QUICKBOOKS_DERIVE_CREDITMEMO;
            $priority = QBDesktopUtilities::QB_ADD_CREDITMEMO_PRIORITY - 2;
        }

        $taskMeta = [
            'action' => $action,
            'object' => QuickBookDesktopTask::CREDIT_MEMO,
            'object_id' => $id,
            'priority' => $priority,
            'origin' => QuickBookDesktopTask::ORIGIN_JP,
            'created_source' => QuickBookDesktopTask::SYSTEM_EVENT,
        ];

        if ($extra) {
            // merge and relace any key exists in the extra with new keys
            $taskMeta = $taskMeta + $extra;
        }

        $parentTask = $this->addTask($qbAction, $userName, $taskMeta);

        if ($child) {
            $child->setParentTask($parentTask);
        }

        return $parentTask;
    }

    function addJpPaymentTask($action, $id, QuickBookDesktopTask $child = null, $userName = null, $extra = [])
    {
        if ($child) {
            $userName = $child->qb_username;
        }

        if ($action == QuickBookDesktopTask::QUERY) {
            $qbAction = QUICKBOOKS_QUERY_RECEIVEPAYMENT;
            $priority = QBDesktopUtilities::QB_QUERY_RECEIVEPAYMENT_PRIORITY;
        }

        if ($action == QuickBookDesktopTask::CREATE) {
            $qbAction = QUICKBOOKS_ADD_RECEIVEPAYMENT;
            $priority = QBDesktopUtilities::QUICKBOOKS_ADD_RECEIVEPAYMENT_PRIORITY;
        }

        if ($action == QuickBookDesktopTask::UPDATE) {
            $qbAction = QUICKBOOKS_MOD_RECEIVEPAYMENT;
            $priority = QBDesktopUtilities::QUICKBOOKS_ADD_RECEIVEPAYMENT_PRIORITY - 1;
        }

        if ($action == QuickBookDesktopTask::DELETE) {
            $qbAction = QUICKBOOKS_DERIVE_RECEIVEPAYMENT;
            $priority = QBDesktopUtilities::QUICKBOOKS_ADD_RECEIVEPAYMENT_PRIORITY - 2;
        }

        $taskMeta = [
            'action' => $action,
            'object' => QuickBookDesktopTask::RECEIVEPAYMENT,
            'object_id' => $id,
            'priority' => $priority,
            'origin' => QuickBookDesktopTask::ORIGIN_JP,
            'created_source' => QuickBookDesktopTask::SYSTEM_EVENT,
        ];

        if ($extra) {
            // merge and relace any key exists in the extra with new keys
            $taskMeta = $taskMeta + $extra;
        }

        $parentTask = $this->addTask($qbAction, $userName, $taskMeta);

        if ($child) {
            $child->setParentTask($parentTask);
        }

        return $parentTask;
    }

    function addJpBillTask($action, $id, QuickBookDesktopTask $child = null, $userName = null, $extra = [])
    {
        if ($child) {
            $userName = $child->qb_username;
        }

        if ($action == QuickBookDesktopTask::QUERY) {
            $qbAction = QUICKBOOKS_QUERY_BILL;
            $priority = QBDesktopUtilities::QB_QUERY_BILL_PRIORITY;
        }

        if ($action == QuickBookDesktopTask::CREATE) {
            $qbAction = QUICKBOOKS_ADD_BILL;
            $priority = QBDesktopUtilities::QB_ADD_BILL_PRIORITY;
        }

        if ($action == QuickBookDesktopTask::UPDATE) {
            $qbAction = QUICKBOOKS_MOD_BILL;
            $priority = QBDesktopUtilities::QB_ADD_BILL_PRIORITY - 1;
        }

        if ($action == QuickBookDesktopTask::DELETE) {
            $qbAction = QUICKBOOKS_DERIVE_BILL;
            $priority = QBDesktopUtilities::QB_ADD_BILL_PRIORITY - 2;
        }

        $taskMeta = [
            'action' => $action,
            'object' => QuickBookDesktopTask::BILL,
            'object_id' => $id,
            'priority' => $priority,
            'origin' => QuickBookDesktopTask::ORIGIN_JP,
            'created_source' => ine($extra, 'created_source') ? $extra['created_source'] : QuickBookDesktopTask::SYSTEM_EVENT,
        ];

        if ($extra) {
            // merge and relace any key exists in the extra with new keys
            $taskMeta = $taskMeta + $extra;
        }

        $parentTask = $this->addTask($qbAction, $userName, $taskMeta);

        if ($child) {
            $child->setParentTask($parentTask);
        }

        return $parentTask;
    }

    function addJpServiceItemTask($action, $id, QuickBookDesktopTask $child = null, $userName = null, $extra = [])
    {
        if ($child) {
            $userName = $child->qb_username;
        }

        if ($action == QuickBookDesktopTask::QUERY) {
            $qbAction = QUICKBOOKS_QUERY_SERVICEITEM;
            $priority = QBDesktopUtilities::QB_QUERY_SERVICE_ITEM_PRIORITY;
        }

        if ($action == QuickBookDesktopTask::CREATE) {
            $qbAction = QUICKBOOKS_ADD_SERVICEITEM;
            $priority = QBDesktopUtilities::QB_ADD_SERVICE_ITEM_PRIORITY;
        }

        if ($action == QuickBookDesktopTask::UPDATE) {
            $qbAction = QUICKBOOKS_MOD_SERVICEITEM;
            $priority = QBDesktopUtilities::QB_ADD_SERVICE_ITEM_PRIORITY - 1;
        }

        if ($action == QuickBookDesktopTask::DELETE) {
            $qbAction = QUICKBOOKS_DERIVE_ITEM;
            $priority = QBDesktopUtilities::QB_DELETE_SERVICE_ITEM_PRIORITY - 2;
        }

        $taskMeta = [
            'action' => $action,
            'object' => QuickBookDesktopTask::ITEM,
            'object_id' => $id,
            'priority' => $priority,
            'origin' => QuickBookDesktopTask::ORIGIN_JP,
            'created_source' => QuickBookDesktopTask::SYSTEM_EVENT,
        ];

        if ($extra) {
            // merge and relace any key exists in the extra with new keys
            $taskMeta = $taskMeta + $extra;
        }

        $parentTask = $this->addTask($qbAction, $userName, $taskMeta);

        if ($child) {
            $child->setParentTask($parentTask);
        }

        return $parentTask;
    }

    function addJpDiscountItemTask($action, $id, QuickBookDesktopTask $child = null, $userName = null, $extra = [])
    {
        if ($child) {
            $userName = $child->qb_username;
        }

        if ($action == QuickBookDesktopTask::QUERY) {
            $qbAction = QUICKBOOKS_QUERY_DISCOUNTITEM;
            $priority = QBDesktopUtilities::QB_ADD_DISCOUNT_ITEM_PRIORITY;
        }

        if ($action == QuickBookDesktopTask::CREATE) {
            $qbAction = QUICKBOOKS_ADD_DISCOUNTITEM;
            $priority = QBDesktopUtilities::QB_ADD_DISCOUNT_ITEM_PRIORITY;
        }

        if ($action == QuickBookDesktopTask::UPDATE) {
            $qbAction = QUICKBOOKS_MOD_DISCOUNTITEM;
            $priority = QBDesktopUtilities::QB_ADD_DISCOUNT_ITEM_PRIORITY - 1;
        }

        if ($action == QuickBookDesktopTask::DELETE) {
            $qbAction = QUICKBOOKS_DERIVE_ITEM;
            $priority = QBDesktopUtilities::QB_ADD_DISCOUNT_ITEM_PRIORITY - 2;
        }

        $taskMeta = [
            'action' => $action,
            'object' => QuickBookDesktopTask::ITEM,
            'object_id' => $id,
            'priority' => $priority,
            'origin' => QuickBookDesktopTask::ORIGIN_JP,
            'created_source' => QuickBookDesktopTask::SYSTEM_EVENT,
        ];

        if ($extra) {
            // merge and relace any key exists in the extra with new keys
            $taskMeta = $taskMeta + $extra;
        }

        $parentTask = $this->addTask($qbAction, $userName, $taskMeta);

        if ($child) {
            $child->setParentTask($parentTask);
        }

        return $parentTask;
    }

    function addJpPaymentMethodTask($action, $id, QuickBookDesktopTask $child = null, $userName = null, $extra = [])
    {
        if ($child) {
            $userName = $child->qb_username;
        }

        if ($action == QuickBookDesktopTask::QUERY) {
            $qbAction = QUICKBOOKS_QUERY_PAYMENTMETHOD;
            $priority = QBDesktopUtilities::QB_QUERY_PAYMENT_METHOD_PRIORITY;
        }

        if ($action == QuickBookDesktopTask::CREATE) {
            $qbAction = QUICKBOOKS_ADD_PAYMENTMETHOD;
            $priority = QBDesktopUtilities::QB_ADD_PAYMENT_METHOD_PRIORITY;
        }

        $taskMeta = [
            'action' => $action,
            'object' => QuickBookDesktopTask::PAYMENT_METHOD,
            'object_id' => $id,
            'priority' => $priority,
            'origin' => QuickBookDesktopTask::ORIGIN_JP,
            'created_source' => QuickBookDesktopTask::SYSTEM_EVENT,
        ];

        if ($extra) {
            // merge and relace any key exists in the extra with new keys
            $taskMeta = $taskMeta + $extra;
        }

        $parentTask = $this->addTask($qbAction, $userName, $taskMeta);

        if ($child) {
            $child->setParentTask($parentTask);
        }

        return $parentTask;
    }

    function addJpTaxCodeTask($action, $id, QuickBookDesktopTask $child = null, $userName = null, $extra = [])
    {
        if ($child) {
            $userName = $child->qb_username;
        }

        if ($action == QuickBookDesktopTask::IMPORT) {
            $qbAction = QUICKBOOKS_IMPORT_SALESTAXCODE;
            $priority = QuickBookDesktopTask::PRIORITY_ADD_SALESTAXCODE;
        }

        $taskMeta = [
            'action' => $action,
            'object' => QuickBookDesktopTask::SALES_TAX_CODE,
            'object_id' => $id,
            'priority' => $priority,
            'origin' => QuickBookDesktopTask::ORIGIN_JP,
            'created_source' => QuickBookDesktopTask::SYSTEM_EVENT,
        ];

        if ($extra) {
            // merge and relace any key exists in the extra with new keys
            $taskMeta = $taskMeta + $extra;
        }

        if(ine($extra, 'priority')) {
            $taskMeta['priority'] = $extra['priority'];
        }

        if (ine($extra, 'created_source')) {
            $taskMeta['created_source'] = $extra['created_source'];
        }

        $parentTask = $this->addTask($qbAction, $userName, $taskMeta);

        if ($child) {
            $child->setParentTask($parentTask);
        }

        return $parentTask;
    }

    function addJpSalesTaxItemTask($action, $id, QuickBookDesktopTask $child = null, $userName = null, $extra = [])
    {
        if ($child) {
            $userName = $child->qb_username;
        }

        if ($action == QuickBookDesktopTask::CREATE) {
            $qbAction = QUICKBOOKS_ADD_SALESTAXITEM;
            $priority = QBDesktopUtilities::QB_ADD_INVOICE_PRIORITY + 1;
        }

        if ($action == QuickBookDesktopTask::UPDATE) {
            $qbAction = QUICKBOOKS_ADD_SALESTAXITEM;
            $priority = QBDesktopUtilities::QB_ADD_INVOICE_PRIORITY + 2;
        }

        $taskMeta = [
            'action' => $action,
            'object' => QuickBookDesktopTask::ITEM_SALES_TAX,
            'object_id' => $id,
            'priority' => $priority,
            'origin' => QuickBookDesktopTask::ORIGIN_JP,
            'created_source' => QuickBookDesktopTask::SYSTEM_EVENT,
        ];

        if ($extra) {
            // merge and relace any key exists in the extra with new keys
            $taskMeta = $taskMeta + $extra;
        }

        if (ine($extra, 'priority')) {
            $taskMeta['priority'] = $extra['priority'];
        }

        if (ine($extra, 'created_source')) {
            $taskMeta['created_source'] = $extra['created_source'];
        }

        $parentTask = $this->addTask($qbAction, $userName, $taskMeta);

        if ($child) {
            $child->setParentTask($parentTask);
        }

        return $parentTask;
    }

    function addJpEstimateTask($action, $id, QuickBookDesktopTask $child = null, $userName = null, $extra = [])
    {
        if ($child) {
            $userName = $child->qb_username;
        }

        if ($action == QuickBookDesktopTask::QUERY) {
            $qbAction = QUICKBOOKS_QUERY_ESTIMATE;
            $priority = QBDesktopUtilities::QB_QUERY_ESTIMATE_PRIORITY;
        }

        if ($action == QuickBookDesktopTask::CREATE) {
            $qbAction = QUICKBOOKS_ADD_ESTIMATE;
            $priority = QBDesktopUtilities::QB_ADD_ESTIMATE_PRIORITY;
        }

        if ($action == QuickBookDesktopTask::UPDATE) {
            $qbAction = QUICKBOOKS_MOD_ESTIMATE;
            $priority = QBDesktopUtilities::QB_MOD_ESTIMATE_PRIORITY;
        }

        if ($action == QuickBookDesktopTask::DELETE) {
            $qbAction = QUICKBOOKS_DELETE_TXN;
            $priority = QBDesktopUtilities::QB_MOD_ESTIMATE_PRIORITY - 1;
        }

        $taskMeta = [
            'action' => $action,
            'object' => QuickBookDesktopTask::ESTIMATE,
            'object_id' => $id,
            'priority' => $priority,
            'origin' => QuickBookDesktopTask::ORIGIN_JP,
            'created_source' => QuickBookDesktopTask::SYSTEM_EVENT,
        ];

        if ($extra) {
            // merge and relace any key exists in the extra with new keys
            $taskMeta = $taskMeta + $extra;
        }

        if (ine($extra, 'created_source')) {
            $taskMeta['created_source'] = $extra['created_source'];
        }

        $parentTask = $this->addTask($qbAction, $userName, $taskMeta);

        if ($child) {
            $child->setParentTask($parentTask);
        }

        return $parentTask;
    }
}
