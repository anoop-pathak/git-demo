<?php

namespace App\Http\Controllers;

use App\Exceptions\QuickBookException;
use App\Exceptions\AuthorizationException;
use App\Models\ApiResponse;
use App\Models\JobCredit;
use App\Repositories\JobCreditRepository;
use FlySystem;
use QBDesktopQueue;
use App\Transformers\JobCreditTransformer;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Request;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;
use PDF;
use App\Services\Credits\JobCredits;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Exceptions\UnauthorizedException;
use App\Services\QuickBooks\Facades\CreditMemo as QBCreditMemo;
use App\Events\CreditCreated;
use App\Events\CreditApplied;
use Illuminate\Support\Facades\Event;
use Exception;
use QuickBooks;

class JobCreditsController extends Controller
{

    public function __construct(JobCreditRepository $repo, Larasponse $response, JobCredits $credit)
    {
        $this->repo = $repo;
        $this->response = $response;
        $this->credit = $credit;

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }

        parent::__construct();
    }

    /**
     * Display a listing of the credit note.
     *
     * @return Response
     */
    public function index()
    {
        $input = Request::all();
        $validator = Validator::make($input, ['job_id' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $jobRepo = App::make(\App\Repositories\JobRepository::class);
        $job = $jobRepo->getById($input['job_id']);

        //send all project job credit include parent job
        if ($job->isMultiJob()) {
            $jobId = $job->id;
            $jobCredits = JobCredit::whereIn('job_id', function ($query) use ($jobId) {
                $query->select('id')->from('jobs')->whereParentId($jobId);
                $query->orWhere('id', $jobId);
            })->orderBy('id', 'desc');
        } else {
            $jobCredits = $this->repo->getFilteredJobCredit($input);
        }

        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

        if (!$limit) {
            $jobCredits = $jobCredits->get();

            return ApiResponse::success($this->response->collection($jobCredits, new JobCreditTransformer));
        }
        $jobCredits = $jobCredits->paginate($limit);

        return ApiResponse::success($this->response->paginatedCollection($jobCredits, new JobCreditTransformer));
    }

    /**
     * Store a newly created resource in credit note.
     *
     * @return Response
     */
    public function store()
    {
        $input = Request::all();

        $validator = Validator::make($input, JobCredit::getRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        DB::beginTransaction();
        try {
            $jobCredit   = $this->credit->addCredits($input);
        } catch (AuthorizationException $e) {
            DB::rollBack();

            return ApiResponse::errorUnauthorized($e->getMessage());
        } catch(ModelNotFoundException $e){
            DB::rollback();
            return ApiResponse::errorNotFound($e->getMessage());
        } catch(UnauthorizedException $e){
            DB::rollBack();
            return ApiResponse::errorUnauthorized($e->getMessage());
        } catch (QuickBookException $e) {
            DB::rollBack();

            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();

            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
        DB::commit();

        $isQBD = false;

		$userName = QBDesktopQueue::getUsername($jobCredit->company_id);

		if ($userName) {
			$isQBD = QBDesktopQueue::isAccountConnected($userName);
		}

		if ($isQBD) {
			if (!$jobCredit->qb_desktop_txn_id) {
				Event::fire('JobProgress.Credits.Events.CreditCreated', new CreditCreated($jobCredit));
			} else {
				Event::fire('JobProgress.Credits.Events.CreditApplied', new CreditApplied($jobCredit));
			}
		}


        return ApiResponse::success([
            'message' => trans('response.success.saved', ['attribute' => 'Job Credit']),
            'job_credit' => $this->response->item($jobCredit, new JobCreditTransformer)
        ]);
    }

    /**
     * apply Credits on invoices
     *
     * @return Response
     */
    public function applyCredits()
    {
        $input = Request::all();
        $validator = Validator::make($input, JobCredit::getApplyCreditRules());
        if($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        // if(ine($input,'credit_id')){
        //  $jobCredit = JobCredit::findOrFail($input['credit_id']);
        //  if($jobCredit->unapplied_amount< $input['amount']){
        //      return ApiResponse::errorGeneral(trans('response.error.greater_than_unapplied_credit'));
        //  }
        // }
        $jobCredit = false;
        DB::beginTransaction();
        try {
            $jobCredit   = $this->credit->applyCredits($input);
        }catch(ModelNotFoundException $e){
            DB::rollback();
            return ApiResponse::errorNotFound($e->getMessage());
        }catch(UnauthorizedException $e){
            DB::rollBack();
            return ApiResponse::errorUnauthorized($e->getMessage());
        } catch(QuickBookException $e) {
            DB::rollBack();
            return ApiResponse::errorGeneral($e->getMessage());
        } catch(Exception $e) {
            DB::rollBack();
            return ApiResponse::errorInternal(trans('response.error.internal'),$e);
        }
        DB::commit();

        $isQBD = false;

		$userName = QBDesktopQueue::getUsername($jobCredit->company_id);

		if ($userName) {
			$isQBD = QBDesktopQueue::isAccountConnected($userName);
		}

		if ($isQBD && $jobCredit) {

			if (!$jobCredit->qb_desktop_txn_id) {
				Event::fire('JobProgress.Credits.Events.CreditCreated', new CreditCreated($jobCredit));
			} else {
				Event::fire('JobProgress.Credits.Events.CreditApplied', new CreditApplied($jobCredit));
			}
		}

        return ApiResponse::success([
            'message'   => trans('response.success.credit_link', ['attribute' => 'Job Credit']),
        ]);
    }

    /**
     * Display the credit note.
     *
     * @param  int $id
     * @return Response
     */
    public function show($id)
    {
        $jobCredit = $this->repo->getById($id);

        return ApiResponse::success([
            'job_credit' => $this->response->item($jobCredit, new JobCreditTransformer)
        ]);
    }

    /**
     * Remove the specified credit note.
     *
     * @param  int $id
     * @return Response
     */
    public function destroy($id)
    {
        $jobCredit = $this->repo->getById($id);
        try {
            $jobCredit->delete();

            return ApiResponse::success([
                'message' => trans('response.success.deleted', ['attribute' => 'Job Credit']),
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * sync credits
     *
     * @return Response
     */
    public function syncCredits()
    {
        $input = Request::all();
        $validator = Validator::make($input, ['id'=>'required', 'job_id' => 'required']);
        if($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        DB::beginTransaction();
        try {
            $token = QuickBooks::getToken();

			if(!$token){
				return ApiResponse::errorGeneral(
					trans('response.error.not_connected', ['attribute' => 'QuickBook Account'])
				);
			}

            // save in database
            $jobCredit   = $this->credit->syncCredits($input['id'], $input['job_id']);
            // create description for pdf
        }catch(ModelNotFoundException $e){
            DB::rollback();
            return ApiResponse::errorNotFound($e->getMessage());
        }catch(UnauthorizedException $e){
            DB::rollBack();
            return ApiResponse::errorUnauthorized($e->getMessage());
        } catch(QuickBookException $e) {
            DB::rollBack();
            return ApiResponse::errorGeneral($e->getMessage());
        } catch(Exception $e) {
            DB::rollBack();
            return ApiResponse::errorInternal(trans('response.error.internal'),$e);
        }
        DB::commit();
        return ApiResponse::success([
            'message'   => trans('response.success.credit_link', ['attribute' => 'Job Credit']),
        ]);
    }


    /**
     * cancel job credit.
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function cancel($id)
    {
        $jobCredit = $this->repo->getById($id);
        DB::beginTransaction();
        try {
            $this->credit->cancelJobCredit($jobCredit);
        } catch(UnauthorizedException $e){
            DB::rollBack();
            return ApiResponse::errorUnauthorized($e->getMessage());
        } catch(QuickBookException $e) {
            DB::rollBack();
            return ApiResponse::errorGeneral($e->getMessage());
        } catch(Exception $e) {
            DB::rollBack();
            return ApiResponse::errorInternal(trans('response.error.internal'),$e);
        }
        DB::commit();
        return ApiResponse::success([
            'message'   => trans('response.success.canceled', ['attribute' => 'Job Credit']),
        ]);
    }

    /**
     * get job credit note pdf
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function getPdfPrint($id)
    {
        $jobCredit = $this->repo->getById($id);
        try {
            $path = config('jp.BASE_PATH') . $jobCredit->file_path;
            $headers = [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'filename="job-credit-note.pdf"'
            ];

            if($jobCredit->quickbook_id) {

				$pdf = QBCreditMemo::getPdf($jobCredit);

				if($pdf) {

					return response($pdf, 200, $headers);
				}
			}

			$fileResource = FlySystem::read($path);

            return response($fileResource, 200, $headers);
        } catch (\Exception $e) {
            $errorDetail = $e->getLine() . ' ' . $e->getFile() . ' ' . $e->getMessage();

            return view('error-page', [
                'errorDetail' => $errorDetail,
                'message' => trans('response.error.error_page'),
            ]);
        }
    }


    /************************ PRIVATE METHOD *******************/

    private function createQuickbookCreditNote($description, $creditMemo)
    {
        $quickbook = App::make(\App\Services\QuickBooks\QuickBookService::class);
        $token = $quickbook->getToken();
        $quickbook->createCreditNote($token, $creditMemo, $description);

        return false;
    }

    private function createCreditNotePdf($description, $jobCredit)
    {
        $company = $jobCredit->company;
        $fileName = $jobCredit->id . Carbon::now()->timestamp . '.pdf';
        $basePath = 'job_credits/' . $fileName;
        $fullPath = config('jp.BASE_PATH') . $basePath;

        $pdf = PDF::loadView('jobs.job-credit-note', [
            'jobCredit' => $jobCredit,
            'customer' => $jobCredit->customer,
            'company' => $company,
            'description' => $description
        ])->setOption('page-size', 'A4')
            ->setOption('margin-left', 0)
            ->setOption('margin-right', 0)
            ->setOption('dpi', 200);

        FlySystem::write($fullPath, $pdf->output(), ['ContentType' => 'application/pdf']);
        $jobCredit->update([
            'file_path' => $basePath,
            'file_size' => FlySystem::getSize($fullPath)
        ]);

        return true;
    }

    private function getJobTradeDescription($job)
    {
        $trades = $job->trades->pluck('name')->toArray();
        $description = $job->number;

        // Append Other trade type decription if 'Other' trade is associated..
        if (in_array('OTHER', $trades) && ($job->other_trade_type_description)) {
            $otherKey = array_search('OTHER', $trades);
            unset($trades[$otherKey]);
            $other = 'OTHER - ' . $job->other_trade_type_description;
            array_push($trades, $other);
        }

        if ($trade = implode(', ', $trades)) {
            $description .= ' / ' . $trade;
        }

        return $description;
    }
}
