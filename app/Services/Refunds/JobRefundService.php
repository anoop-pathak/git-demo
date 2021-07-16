<?php
namespace App\Services\Refunds;

use Illuminate\Support\Facades\DB;
use App\Models\Job;
use App\Models\Company;
use App\Repositories\JobRepository;
use App\Repositories\FinancialAccountRepository;
use App\Repositories\JobRefundRepository;
use Carbon\Carbon;
use App\Models\JobFinancialCalculation;
use FlySystem;
use App\Services\Refunds\Helpers\CreateRefundHelper;
use App\Exceptions\MinRefundAmountException;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use PDF;

class JobRefundService
{
	public function __construct(JobRepository $jobRepo, FinancialAccountRepository $financialAccountRepo, JobRefundRepository $repo)
	{
		$this->repo = $repo;
		$this->jobRepo = $jobRepo;
		$this->financialAccountRepo = $financialAccountRepo;
	}

	/**
	 * Create refund for the customer.
	 *
	 * @param Integer $customerId
	 * @param Integer $jobId
	 * @param Integer $accountId
	 * @param Array $lines
	 * @param Array $meta
	 * @return JobRefund
	 */
	public function createJobRefund($customerId, $jobId, $accountId, $lines, $meta = array())
	{
		try {
			DB::beginTransaction();
			$requestData = new CreateRefundHelper;
			$requestData->setCustomerId($customerId)
					->setJobId($jobId)
					->setFinancialAccountId($accountId)
					->setRefundLines($lines)
					->setAdditionalData($meta);

			$this->jobRepo->getByid($requestData->getJobId());
			$this->financialAccountRepo->getRefundAccountById($requestData->getFinancialAccountId());

			$totalAmount = $requestData->getTotalAmount();

			if($totalAmount < 0) {
				throw new MinRefundAmountException(trans('response.error.least_amount', ['attribute' => 'Refund']));
			}

			//save refund and entities
			$refund = $this->repo->save($requestData);
			
			// create PDF for Job Refund
			$this->updatePdf($refund);

			JobFinancialCalculation::updateFinancials($refund->job_id);

			DB::commit();
			return $refund;
		} catch (Exception $e) {
			DB::rollback();
			throw $e;
		}
	}

	/**
	 * cancel job refund.
	 * @param  $id
	 * @return boolean
	 */
	public function cancelJobRefund($jobRefund, $meta)
	{
		$jobRefund->update([
			'canceled_at' => Carbon::now()->toDateTimeString(),
			'canceled_by' => Auth::id(),
			'cancel_note' => $meta['cancel_note']
		]);
		
		JobFinancialCalculation::updateFinancials($jobRefund->job_id);

		return true;
	}

	/**
	 * Update refund for the customer.
	 *
	 * @param object $refund
	 * @param Integer $accountId
	 * @param Array $lines
	 * @param Array $meta
	 * @return JobRefund
	 */
	public function updateJobRefund($refund, $accountId, $lines, $meta = array())
	{
		try {
			DB::beginTransaction();

			$requestData = new CreateRefundHelper;
			$requestData->setRefundLines($lines)
				->setFinancialAccountId($accountId)
				->setAdditionalData($meta);

			//update refund and entities
			$refund = $this->repo->updateRefund($refund, $requestData, $meta);
			// create PDF for Job Refund
			$this->updatePdf($refund);

			JobFinancialCalculation::updateFinancials($refund->job_id);

			DB::commit();
			return $refund;
		} catch (Exception $e) {
			DB::rollback();
			throw $e;
		}
	}

	/************************ PRIVATE METHOD *******************/

	/**
	 * Update Job Refund Pdf
	 * @param  Instance $refund Job Refund
	 * @return Job Refund
	 */
	public function updatePdf($refund)
	{
		$job = $refund->job;
		$customer = $job->customer;
		$company  = Company::find($refund->company_id);

		$oldFilePath = null;
		if(!empty($refund->file_path)) {
			$oldFilePath = config('jp.BASE_PATH').$refund->file_path;
		}

		$fileName =   $refund->id.'_'. timestamp().'.pdf';
		$baseName = 'job_refunds/'.$fileName;
		$fullPath = config('jp.BASE_PATH') . $baseName;
			
		$contents = View::make('jobs.job_refund',[
			'refund'	=> $refund,
			'customer'	=> $customer,
			'company'	=> $company,
			'job'		=> $job
		])->render();

		$pdf = PDF::loadHTML($contents)->setOption('page-size','A4')
			->setOption('margin-left',0)
			->setOption('margin-right',0)
			->setOption('dpi', 200);

		FlySystem::put($fullPath, $pdf->output(), ['ContentType' => 'application/pdf']);

		$refund->file_path = $fullPath;
		$refund->update();

		$this->fileDelete($oldFilePath);

		return $refund;
	}

	/**
	 * File delete
	 * @param  url $oldFilePath  Old file Path Url
	 * @return Boolan
	 */
	private function fileDelete($oldFilePath)
	{
		if(!$oldFilePath) return;
		try {
			FlySystem::delete($oldFilePath);
		} catch(Exception $e) {
			// nothing to do.
		}
	}
}