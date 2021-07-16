<?php
namespace App\Services\QuickBookDesktop;

use Exception;
use Illuminate\Support\Facades\Log;
use App\Services\QuickBookDesktop\Entity\ReceivePayment as QBDReceivePayment;
use App\Services\QuickBookDesktop\Entity\Invoice as QBDInvoice;
use App\Services\QuickBookDesktop\Entity\CreditMemo as QBDCreditMemo;
use App\Services\QuickBookDesktop\Entity\Bill as QBDBill;
use App\Services\QuickBookDesktop\Entity\Job as QBDJob;
use App\Services\QuickBookDesktop\Entity\Customer as QBDCustomer;
use App\Services\QuickBookDesktop\Entity\Account as QBDAccount;
use App\Services\QuickBookDesktop\Entity\Vendor as QBDVendor;
use App\Models\QuickBookDesktopTask;
use App\Models\QuickbookSyncCustomer;
use App\Models\Customer;
use App\Models\Job;
use App\Models\JobInvoice;
use App\Models\JobCredit;
use App\Models\JobPayment;
use App\Models\FinancialAccount;
use App\Models\Vendor;
use App\Models\VendorBill;

class SyncStatus
{

    public function __construct(
        QBDReceivePayment $qbdReceivePayment,
        QBDInvoice $qbdInvoice,
        QBDCreditMemo $qbdCreditMemo,
        QBDBill $qbdBill,
        QBDCustomer $qbdCustomer,
        QBDJob $qbdJob,
        QBDAccount $qbdAccount,
        QBDVendor $qbdVendor
    ) {
        $this->qbdReceivePayment = $qbdReceivePayment;
        $this->qbdInvoice = $qbdInvoice;
        $this->qbdCreditMemo = $qbdCreditMemo;
        $this->qbdBill = $qbdBill;
        $this->qbdCustomer = $qbdCustomer;
        $this->qbdJob = $qbdJob;
        $this->qbdAccount = $qbdAccount;
        $this->qbdVendor = $qbdVendor;
    }

    public function update($entity, $entityId, $status, $origin = null)
	{

        try {

            if (!$entity || !$entityId) {
                return false;
            }

            $syncStatus = null;

            $syncStatus = $this->getStatus($status);

            if ($entity == QuickBookDesktopTask::CUSTOMER) {

                if ($origin == QuickBookDesktopTask::ORIGIN_JP) {
                    $customer = Customer::find($entityId);

                } else if ($origin == QuickBookDesktopTask::ORIGIN_QBD) {

                    $customer = Customer::where('qb_desktop_id', $entityId)
                        ->where('company_id', getScopeId())
                        ->first();

                    if (!$customer) {

                        $job = $this->qbdJob->getJobByQbdId($entityId);

                        if ($job && empty($job->ghost_job)) {

                            Job::withTrashed()->where('id', $job->id)
                                ->update(['qb_desktop_sync_status' => $syncStatus]);

                            $job->qb_desktop_sync_status = $syncStatus;

                            return $job;
                        }
                    }
                }

                if (empty($customer)) {
                    Log::info("Update Sync: Customer not found");
                    return false;
                }

                Customer::where('id', $customer->id)->update(['qb_desktop_sync_status' => $syncStatus]);
                $customer->qb_desktop_sync_status = $syncStatus;

                return $customer;
            }

            if ($entity == QuickBookDesktopTask::JOB && ($origin == QuickBookDesktopTask::ORIGIN_QBD)) {

                $job = $this->qbdJob->getJobByQbdId($entityId);

                if ($job) {

                    Job::withTrashed()->where('id', $job->id)
                        ->update(['qb_desktop_sync_status' => $syncStatus]);

                    $job->qb_desktop_sync_status = $syncStatus;

                    return $job;
                }

                return false;
            }

            if ($entity == QuickBookDesktopTask::JOB && ($origin == QuickBookDesktopTask::ORIGIN_JP)) {

                $job = null;

                $job = Job::find($entityId);

                if (!$job) {
                    return false;
                }

                // we are updating this with model for stoping model events
                Job::where('id', $job->id)->update([
                    'qb_desktop_sync_status' => $syncStatus
                ]);

                $job->qb_desktop_sync_status = $syncStatus;

                return $job;
            }

            if ($entity == QuickBookDesktopTask::INVOICE) {

                if ($origin == QuickBookDesktopTask::ORIGIN_JP) {

                    $invoice = JobInvoice::find($entityId);

                } else if ($origin == QuickBookDesktopTask::ORIGIN_QBD) {

                    $invoice = $this->qbdInvoice->getJobInvoiceByQbdTxnId($entityId);
                }

                if (!$invoice) {
                    Log::info("Update Sync: Invoice not found");
                    return false;
                }

                $invoice->qb_desktop_sync_status = $syncStatus;

                return $invoice->save();
            }

            if ($entity == QuickBookDesktopTask::CREDIT_MEMO) {

                if ($origin == QuickBookDesktopTask::ORIGIN_JP) {

                    $jobCredit = JobCredit::find($entityId);
                } else if ($origin == QuickBookDesktopTask::ORIGIN_QBD) {

                    $jobCredit = $this->qbdCreditMemo->getJobCreditByQbdTxnId($entityId);
                }

                if (!$jobCredit) {
                    Log::info("Update Sync: CreditMemo not found");
                    return false;
                }

                $jobCredit->qb_desktop_sync_status = $syncStatus;

                return $jobCredit->save();
            }

            if ($entity == QuickBookDesktopTask::RECEIVEPAYMENT) {

                if ($origin == QuickBookDesktopTask::ORIGIN_JP) {

                    $jobPayment = JobPayment::find($entityId);

                } else if ($origin == QuickBookDesktopTask::ORIGIN_QBD) {

                    $jobPayment = $this->qbdReceivePayment->getJObPaymentByQbdTxnId($entityId);
                }

                if (!$jobPayment) {
                    Log::info("Update Sync: Payment not found");
                    return false;
                }

                // we are updating this with model for stoping model events
                JobPayment::where('id', $jobPayment->id)
                    ->update(['qb_desktop_sync_status' => $syncStatus]);

                $jobPayment->qb_desktop_sync_status = $syncStatus;

                return $jobPayment;
            }

            if ($entity == QuickBookDesktopTask::ACCOUNT) {

                if ($origin == QuickBookDesktopTask::ORIGIN_JP) {

                    $account = FinancialAccount::withTrashed()->find($entityId);

                } else if ($origin == QuickBookDesktopTask::ORIGIN_QBD) {

                    $account = $this->qbdAccount->getAccountByQbdId($entityId);
                }

                if (!$account) {
                    Log::info("Update Sync: Account not found");
                    return false;
                }

                // we are updating this with model for stoping model events
                FinancialAccount::where('id', $account->id)
                    ->update(['qb_desktop_sync_status' => $syncStatus]);

                $account->qb_desktop_sync_status = $syncStatus;

                return $account;
            }

            if ($entity == QuickBookDesktopTask::VENDOR) {

                if ($origin == QuickBookDesktopTask::ORIGIN_JP) {

                    $vendor = Vendor::withTrashed()->find($entityId);

                } else if ($origin == QuickBookDesktopTask::ORIGIN_QBD) {

                    $vendor = $this->qbdVendor->getVendorByQbdId($entityId);
                }

                if (!$vendor) {
                    Log::info("Update Sync: Vendor not found");
                    return false;
                }

                // we are updating this with model for stoping model events
                Vendor::where('id', $vendor->id)
                    ->update(['qb_desktop_sync_status' => $syncStatus]);

                $vendor->qb_desktop_sync_status = $syncStatus;

                return $vendor;
            }

            if ($entity == QuickBookDesktopTask::BILL) {

                if ($origin == QuickBookDesktopTask::ORIGIN_JP) {

                    $bill = VendorBill::withTrashed()->find($entityId);

                } else if ($origin == QuickBookDesktopTask::ORIGIN_QBD) {

                    $bill = $this->qbdBill->getBillByQbdId($entityId);
                }

                if (!$bill) {
                    Log::info("Update Sync: Bill not found");
                    return false;
                }

                VendorBill::where('id', $bill->id)
                    ->update(['qb_desktop_sync_status' => $syncStatus]);

                $bill->qb_desktop_sync_status = $syncStatus;

                return $bill;
            }

        } catch (Exception $e) {

            Log::info("Update Sync: Error", [$e->getMessage()]);
            return false;
        }
    }

    public function getStatus($status)
	{
		$syncStatus = null;

		switch ($status) {

			case QuickBookDesktopTask::STATUS_INPROGRESS:
				$syncStatus = '0';
				break;
			case QuickBookDesktopTask::STATUS_SUCCESS:
				$syncStatus = '1';
				break;
			case QuickBookDesktopTask::STATUS_ERROR:
				$syncStatus = '2';
				break;
			default:
				break;
		}

		return $syncStatus;
	}

    /***
     * Update QuickBook Sync Customer Status.
     */
    public function updateCustomerAccountSyncStatus($groupId, $qbUsername)
    {
        if(!$groupId || !$qbUsername){
            return false;
        }

        $status = null;

        $totalTaskCount = QuickBookDesktopTask::where('qb_username', $qbUsername)
            ->where('group_id', $groupId)
            ->count();

        $successTaskCount = QuickBookDesktopTask::where('qb_username', $qbUsername)
            ->where('group_id', $groupId)
            ->where('status', QuickBookDesktopTask::STATUS_SUCCESS)
            ->count();

        $failedTaskCount = QuickBookDesktopTask::where('qb_username', $qbUsername)
            ->where('group_id', $groupId)
            ->where('status', QuickBookDesktopTask::STATUS_ERROR)
            ->count();

            if(!empty($failedTaskCount)){
                $status = QuickbookSyncCustomer::SYNC_FAILED;
            }

            if($totalTaskCount == $successTaskCount){
                $status = QuickbookSyncCustomer::SYNC_COMPLETE;
            }

        if($status){
            QuickbookSyncCustomer::where('group_id', $groupId)
                ->update(['sync_status' => $status]);
        }

        return true;

    }
}