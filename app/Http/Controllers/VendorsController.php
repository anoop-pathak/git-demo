<?php
namespace App\Http\Controllers;

use App\Transformers\VendorsTransformer;
use App\Repositories\VendorRepository;
use Sorskod\Larasponse\Larasponse;
use App\Services\Contexts\Context;
use App\Events\VendorDeleted;
use App\Events\VendorCreated;
use App\Events\VendorUpdated;
use App\Services\QuickBooks\Exceptions\UnauthorizedException;
use App\Services\QuickBooks\Exceptions\QuickBookException;
use App\Services\QuickBookDesktop\Facades\TaskScheduler as QBDesktopTaskScheduler;
use Request;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;
use App\Models\ApiResponse;
use App\Models\Vendor;
use QBDesktopQueue;
use App\Services\QuickBooks\Facades\QuickBooks;
use App\Services\QuickBooks\Entity\Vendor as QBVendor;

class VendorsController extends ApiController
{

	/**
	 * Vendor Repo
	 * @var \JobProgress\Repositories\VendorRepositories
	 */
	protected $repo;

	/**
	 * Display a listing of the resource.
	 * @return Response
	 */
	protected $response;

	/**
	 * Set Company Scope
	 * @return company scope
	 */
	protected $scope;

	public function __construct(Larasponse $response, VendorRepository $repo, Context $scope)
	{
		$this->repo = $repo;
		$this->scope = $scope;
		$this->response = $response;

		parent::__construct();

		if(Request::get('includes')) {
			$this->response->parseIncludes(Request::get('includes'));
		}
	}

	public function index()
	{
		$input = Request::all();

		$vendors = $this->repo->getFilteredVendors($input);

		$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

		$vendors = $vendors->paginate($limit);

		return ApiResponse::success($this->response->paginatedCollection($vendors,new VendorsTransformer));
	}

	/**
	 * Store a newly created resource in storage.
	 * POST /vendors
	 *
	 * @return Response
	 */
	public function store()
	{
		$input = Request::all();
		$validator = Validator::make($input, Vendor::getCreateRules());
		if($validator->fails()) {

			return ApiResponse::validation($validator);
		}

		try {
			$vendor = $this->repo->createVendor($input['display_name'], $input);
			Event::fire('JobProgress.Events.VendorCreated', new VendorCreated($vendor));

			return ApiResponse::success([
				'message' => trans('response.success.created', ['attribute' => 'vendor']),
				'data' => $this->response->item($vendor, new VendorsTransformer)
			]);
		}catch(Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}
	/**
	 * Update the specified resource in storage.
	 * PUT /customers/{id}
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id)
	{
		$input = Request::all();

		$validator = Validator::make($input, Vendor::getUpdateRules($id));
		if($validator->fails()) {

			return ApiResponse::validation($validator);
		}
		$vendor = $this->repo->getById($id);
		try {
			$vendor = $this->repo->updateVendor($vendor, $input['display_name'], $input);
			Event::fire('JobProgress.Events.VendorUpdated', new VendorUpdated($vendor));

			return ApiResponse::success([
				'message' => trans('response.success.updated', ['attribute' => 'Vendor']),
				'data' => $this->response->item($vendor, new VendorsTransformer)
			]);
		}catch(Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}

	/**
	 * Remove the specified resource from storage.
	 * DELETE /customers/{id}
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
		if(!Auth::user()->isAuthority()) {

			return ApiResponse::errorForbidden();
		}

		$vendor = $this->repo->getById($id);
		try {
			$vendor->delete();

			logx("Asd");

			Event::fire('JobProgress.Events.VendorDeleted', new VendorDeleted($vendor));

			return ApiResponse::success([
				'message' => trans('response.success.deleted', ['attribute' => 'Vendor']),
			]);
		} catch(Exception $e){

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}

	public function active($id)
	{
		$input = Request::onlyLegacy('active');

		$vendor = $this->repo->getByIdWithTrashed($id);
		try {
			if(ine($input, 'active')){
				for ($i = 0; ; $i++){
					$displayName = $vendor->display_name;
					if($i > 0) {
						$displayName .= '-'.$i;
					}
					$restoreVendor = Vendor::where('display_name', $displayName)
						->where('company_id', $this->scope->id())
						->first();
					if($restoreVendor) continue;
					$vendor->update(['display_name' => $displayName]);
					break;
				}
				$msg = 'active';
				$vendor->restore();
				Event::fire('JobProgress.Events.VendorUpdated', new VendorUpdated($vendor));
			} else {
				$msg = 'inactive';
				$vendor->delete();
				Event::fire('JobProgress.Events.VendorDeleted', new VendorDeleted($vendor));
			}

			return ApiResponse::success([
				'message' => trans('response.success.mark_as', [
					'attribute' => 'Vendor',
					'as_attribute' => $msg
				])
			]);

		} catch(Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}

	public function syncOnQBO()
	{
		try{
			set_time_limit(0);

			if($token = QBDesktopQueue::isAccountConnected()) {
				$message = 'Vendors task queued successfully for desktop syncing.';
				QBDesktopQueue::allVendorsSync();
			}else{
				$token = QuickBooks::isConnected();
				$message = 'Vendors synced with QuickBook Online successfully.';
				$app = App::make(QBVendor::class);
				$app->actionSynchAll();
			}

			if(!$token) {
				return ApiResponse::errorGeneral(
					trans('response.error.not_connected', ['attribute' => 'QuickBook Account'])
				);
			}

			return ApiResponse::success(['message' => $message]);
		} catch(UnauthorizedException $e){
			return ApiResponse::errorGeneral($e->getMessage());
		} catch(QuickBookException $e){
			return ApiResponse::errorGeneral($e->getMessage());
		} catch(\Exception $e){
			return ApiResponse::errorGeneral($e->getMessage());
		}
	}
}