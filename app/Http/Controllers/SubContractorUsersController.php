<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\FinancialCategory;
use App\Models\FinancialProduct;
use App\Models\Labour;
use App\Models\Resource;
use App\Models\User;
use App\Models\UserProfile;
use App\Repositories\LabourRepository;
use FlySystem;
use App\Services\Resources\ResourceServices;
use App\Services\Users\AuthenticationService;
use App\Transformers\SubContractorsTransformer;
use App\Events\SubContractorDeleted;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Request;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;
use App\Repositories\UserRepository;
use Lang;
use Illuminate\Support\Facades\Event;
use App\Helpers\SecurityCheck;
use Illuminate\Support\Facades\Auth;
use App\Events\ReleaseTwilioNumberForSingleUser;
use App\Services\VendorService;
use App\Events\VendorCreated;
use App\Events\VendorUpdated;
use App\Events\VendorDeleted;
use App\Exceptions\DuplicateVendor;
use App\Events\SubContractorActivateOrDeactivate;

class SubContractorUsersController extends ApiController
{

    /* Larasponse class Instance */
    protected $response;

    /* Labour Repository */
    protected $repo;

    /* ResourceServices class instance */
    protected $resourceService;
    protected $authService;
    protected $vendorService;

    protected $userRepo;

    public function __construct(
        LabourRepository $repo,
        Larasponse $response,
        ResourceServices $resourceService,
        AuthenticationService $authenticationService,
        UserRepository $userRepo,
        VendorService $vendorService
    ) {

        $this->repo = $repo;
        $this->response = $response;
        $this->resourceService = $resourceService;
        $this->authService = $authenticationService;
        $this->userRepo = $userRepo;
        $this->vendorService 	= $vendorService;

        parent::__construct();

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
    }

    /**
     * Display a listing of the resource.
     * GET /sub_contractors
     *
     * @return Response
     */
    public function index()
    {
        $input = Request::all();

        $subContractors = $this->repo->getLabours($input);

        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

        if (!$limit) {
            $subContractors = $subContractors->get();

            return ApiResponse::success($this->response->collection($subContractors, new SubContractorsTransformer));
        }
        $subContractors = $subContractors->paginate($limit);

        return ApiResponse::success($this->response->paginatedCollection($subContractors, new SubContractorsTransformer));
    }

    /**
     * Store a newly created resource in storage.
     * POST /sub_contractors
     *
     * @return Response
     */
    public function store()
    {
        $input = Request::all();

        $input['group_id'] = User::GROUP_SUB_CONTRACTOR;
        $input['company_id'] = $this->repo->getScopeId();

        $rules = array_merge(User::getNonLoggableUserRules(), UserProfile::getCreateRules());
        $rules['rate_sheet'] = 'array';
        $validator = Validator::make($input, $rules);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        DB::beginTransaction();
        try {
            $subContractor = $this->executeCommand('\App\Commands\UserCreateCommand', $input);

            $vendor = $this->vendorService->saveSubContractorVendor($subContractor, $input);

            // assign trades
            if (ine($input, 'trades')) {
                $subContractor = $this->repo->assignTrades($subContractor, (array)$input['trades']);
            }

            // assign worktype
            if (ine($input, 'work_types')) {
                $subContractor = $this->repo->assigWorkTypes($subContractor, (array)$input['work_types']);
            }

            // save sub contractor rate sheet
            if (isset($input['rate_sheet'])) {
                $financial = $this->saveFinancialDetails($subContractor, $input);

                if ($financial instanceof App\CustomValidationRules\CustomValidationRules) {
                    return ApiResponse::validation($financial);
                }
            }

            $resourceId = $this->createResourceDir($subContractor);
            $subContractor->resource_id = $resourceId;
            $subContractor->save();
            $subContractor = User::findOrFail($subContractor->id);
        } catch(DuplicateVendor $e) {
			DB::rollback();

			return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            DB::rollback();
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
        DB::commit();

        Event::fire('JobProgress.Events.VendorCreated', new VendorCreated($vendor));

        return ApiResponse::success([
            'message' => trans('response.success.saved', ['attribute' => 'Sub Contractor']),
            'data' => $this->response->item($subContractor, new SubContractorsTransformer),
        ]);
    }

    /**
     * Display the specified resource.
     * GET /sub_contractors/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function show($id)
    {
        $subContractor = $this->repo->getById($id);

        return ApiResponse::success([
            'data' => $this->response->item($subContractor, new SubContractorsTransformer)
        ]);
    }

    /**
     * Update the specified resource in storage.
     * PUT /sub_contractors/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function update($id)
    {
        $subContractor = $this->repo->getById($id);
        $input = Request::all();

        $input['group_id'] = $subContractor->group_id;
        $oldGroup = $subContractor->group_id;
        $rules = array_merge(User::getNonLoggableUserRules($id), UserProfile::getCreateRules());
        $rules['rate_sheet'] = 'array';
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        if(ine($input, 'email')) {
			$emailExists = User::where('email', $input['email'])
				->where('email', '<>', $subContractor->email)
				->exists();

			if($emailExists) {
				return ApiResponse::errorGeneral(trans('response.error.duplicate_email'));
			}
        }

        $input['id'] = $id;

        DB::beginTransaction();
        $vendor = null;
        try {
            $subContractor = $this->executeCommand('\App\Commands\UserUpdateCommand', $input);

            if($subContractor->group_id === User::GROUP_SUB_CONTRACTOR) {
				$vendor = $this->vendorService->updateSubContractorVendor($subContractor, $input);
			}

            if (ine($input, 'trades')) {
                $subContractor = $this->repo->assignTrades($subContractor, (array)$input['trades']);
            }

            if (ine($input, 'work_types')) {
                $subContractor = $this->repo->assigWorkTypes($subContractor, (array)$input['work_types']);
            }
        } catch(DuplicateVendor $e) {
			DB::rollback();

			return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            DB::rollback();
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
        DB::commit();

        if($vendor) {
			Event::fire('JobProgress.Events.VendorUpdated', new VendorUpdated($vendor));
		}

        return ApiResponse::success([
            'message' => trans('response.success.updated', ['attribute' => 'Sub Contractor']),
            'data' => $this->response->item($subContractor, new SubContractorsTransformer),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /sub_contractors/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        $vendor = null;
        $subContractor = $this->repo->getById($id);
        try {
            $subContractor->financialDetails()->delete();
            // logout from all devices
            $this->authService->logoutFromAllDevices($subContractor->id);

            if($subContractor->group_id === User::GROUP_SUB_CONTRACTOR) {
				$vendor = $this->vendorService->deleteOrRestoreSubContractorVendor($subContractor);
			}
            $subContractor->delete();

            if($subContractor->group_id === User::GROUP_SUB_CONTRACTOR_PRIME) {
                Event::fire('JobProgress.SubContractors.Events.SubContractorDeleted', new SubContractorDeleted($subContractor));
            }

            Event::fire('JobProgress.Twilio.Events.ReleaseTwilioNumberForSingleUser', new ReleaseTwilioNumberForSingleUser($subContractor));

            DB::commit();

            if($vendor) {
				Event::fire('JobProgress.Events.VendorDeleted', new VendorDeleted($vendor));
			}

            return ApiResponse::success([
                'message' => trans('response.success.deleted', ['attribute' => 'Sub Contractor']),
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
	 * Restore a prime sub contractor
	 * RESDTORE /sub_contractors/{id}/restore
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function restore($id)
	{
		if(!Auth::user()->isAuthority()) {
			return ApiResponse::errorForbidden();
		}

		if(!SecurityCheck::verifyPassword()) {
			return SecurityCheck::$error;
		}

		$subContractorPrime = $this->repo->getTrashedSubContractorPrime($id);

		try{
			$subContractorPrime->restore();

            if($subContractorPrime->group_id === User::GROUP_SUB_CONTRACTOR) {
				$this->vendorService->deleteOrRestoreSubContractorVendor($subContractorPrime, true);
			}

		} catch(\Exception $e) {
			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}

		return ApiResponse::success([
			'message' => trans('response.success.restored', ['attribute' => 'Sub Contractor Prime'])
		]);

	}

    /**
     * @ imoprt labours / sub_contractores form excel file
     */
    public function import()
    {
        try {
            $input = Request::onlyLegacy('file');

            $validator = Validator::make($input, Labour::getFileRules());
            if ($validator->fails()) {
                return ApiResponse::validation($validator);
            }
            $records = $this->extractFile($input['file']);

            foreach ($records as $record) {
                $subContractor = $this->executeCommand('\App\Users\UserCreateCommand', $record);
                $resourceId = $this->createResourceDir($subContractor);
                $subContractor->resource_id = $resourceId;
                $subContractor->save();
            }

            return ApiResponse::success([
                'message' => trans('response.success.imported', ['attribute' => 'Sub Contractor']),
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * @ add / change Profile Pic of Labour / sub_contractor
     */
    public function profilePic()
    {
        $input = Request::onlyLegacy('labour_id', 'image');
        $validator = Validator::make($input, Labour::getProfilePicRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $subContractor = $this->repo->getById($input['labour_id']);
        try {
            $this->removeProfilePic($subContractor);
            $profilePic = $this->uploadProfilePic($input);
            $subContractor->profile->profile_pic = $profilePic;
            $subContractor->profile->save();

            return ApiResponse::success([
                'message' => trans('response.success.updated', ['attribute' => 'Profile picture']),
                'data' => [
                    'profile_pic' => FlySystem::publicUrl(config('jp.BASE_PATH') . $profilePic),
                ]
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * @ Delete Profile Pic of Labour / sub_contractor
     */
    public function deleteProfilePic()
    {
        $input = Request::onlyLegacy('labour_id');

        $validator = Validator::make($input, ['labour_id' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $subContractor = $this->repo->getById($input['labour_id']);
        try {
            $this->removeProfilePic($subContractor);
            $subContractor->profile->profile_pic = null;
            $subContractor->profile->save();

            return ApiResponse::success(['message' => trans('response.success.profile_pic_removed')]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * @ Activate - Deactivate sub_contractor
     */
    public function activateSubContractors()
    {
        $input = Request::onlyLegacy('labour_ids', 'is_active');

        $validator = Validator::make($input, Labour::getActivateDeactivateRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $subContractor = $this->repo->getSubById($input['labour_ids']);
        try {
            $this->repo->make()->onlySubContractors()
                ->whereIn('id', (array)$input['labour_ids'])
                ->update(['active' => $input['is_active']]);

            if(!$subContractor->active) {
                Event::fire('JobProgress.Twilio.Events.ReleaseTwilioNumberForSingleUser', new ReleaseTwilioNumberForSingleUser($subContractor));
            }

            if($subContractor->group_id === User::GROUP_SUB_CONTRACTOR_PRIME) {
				Event::fire('JobProgress.SubContractors.Events.SubContractorActivateOrDeactivate', new SubContractorActivateOrDeactivate($subContractor));
			}


            if($subContractor->group_id === User::GROUP_SUB_CONTRACTOR) {
				$this->vendorService->deleteOrRestoreSubContractorVendor($subContractor, $input['is_active']);
			}

            return ApiResponse::success([
                'message' => trans('response.success.updated', ['attribute' => 'Sub Contractor']),
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Update the note for labour
     * PUT /sub_contractors/{id}/update_note
     *
     * @param  int : $id | string : note
     * @return Response
     */
    public function updateNote($id)
    {
        $subContractor = $this->repo->getById($id);
        $input = Request::onlyLegacy('note');
        $subContractor->note = $input['note'];
        if ($subContractor->save()) {
            return ApiResponse::success([
                'message' => trans('response.success.updated', ['attribute' => 'Sub Contractor note']),
            ]);
        }

        return ApiResponse::errorInternal(trans('response.error.internal'));
    }

    /**
     * Update the rating for labour
     * PUT /sub_contractors/{id}/rating
     *
     * @param  int : $id | integer : rating
     * @return Response
     */
    public function updateRating($id)
    {
        $input = Request::onlyLegacy('rating');

        //find labour
        $subContractor = $this->repo->getById($id);

        $validator = Validator::make($input, ['rating' => 'numeric|min:0|max:5']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        //set rating
        $subContractor->rating = $input['rating'];

        if ($subContractor->save()) {
            return ApiResponse::success([
                'message' => trans('response.success.updated', ['attribute' => 'Sub Contractor rating']),
            ]);
        }

        return ApiResponse::errorInternal(trans('response.error.internal'));
    }

     /**
     * change sub-contractor group (for prime sub contractors)
     * PUT /sub_contractors/{id}/change_group
     *
     * @param  $id ID of sub-contractor
     * @return response
     */
    public function updateGroup($id)
    {
        DB::beginTransaction();
        try {
            $input = Request::onlyLegacy('make_prime');
            $validator = Validator::make($input, ['make_prime' => 'boolean']);
            if ($validator->fails()) {
                return ApiResponse::validation($validator);
            }
            $groupId = User::GROUP_SUB_CONTRACTOR;
            if((bool)$input['make_prime']) {
                $groupId = User::GROUP_SUB_CONTRACTOR_PRIME;
            }
            $subContractor = $this->repo->getById($id);
            $subContractor = $this->userRepo->changeSubContractorGroup($subContractor, $groupId);
            $this->vendorService->deleteOrRestoreSubContractorVendor($subContractor, !(bool)$input['make_prime']);
        }catch(\Exception $e) {
            DB::rollback();
            return ApiResponse::errorInternal(Lang::get('response.error.internal'),$e);
        }
        DB::commit();
        return ApiResponse::success([
            'message' => Lang::get('response.success.updated',['attribute' => 'Subcontractor']),
            'user' => $this->response->item($subContractor,new SubContractorsTransformer)
        ]);
    }

    /**
     * enable or disable data masking for sub contractor
     * * PUT /sub_contractors/enable_masking
     *
     * @return response
     */
    public function enableMasking()
    {
        $input = Request::onlyLegacy('sub_id', 'data_masking');
        $validator = Validator::make($input, [
            'sub_id'        => 'required',
            'data_masking'  => 'required',
        ]);
        if( $validator->fails()){
            return ApiResponse::validation($validator);
        }
        $subContractor = $this->repo->getById($input['sub_id']);
        $subContractor->data_masking = (bool)$input['data_masking'];
        $subContractor->save();
        return ApiResponse::success([
            'message' => trans('response.success.updated',['attribute' => 'Subcontractor']),
        ]);
    }

    /************ Private functions ************/

    /**
     * @extract excel file and return valid records
     */
    private function extractFile($file)
    {
        $excel = App::make('excel');
        $filename = $file->getRealPath();
        $import = $excel->load($filename);
        $records = $import->get()->toArray();

        $validRecords = [];
        foreach ($records as $key => $record) {
            $rules = array_merge(User::getNonLoggableUserRules(), UserProfile::getCreateRules());
            $validator = Validator::make($input, $rules);
            if (!$validator->fails()) {
                $record['phones'] = $this->mapPhonesInput($record['phones']);
                $validRecords[$key] = $record;
            }
        }

        return $validRecords;
    }

    /**
     * @return formatted phones
     */
    private function mapPhonesInput($inputPhones)
    {
        $phones = [];
        $numbers = preg_split("/[\s;]+/", str_replace(['(', ')', '-', ','], '', trim($inputPhones)));
        foreach ($numbers as $key => $number) {
            if (empty($number)) {
                continue;
            }

            if (!preg_match('/^[0-9-]+$/', $number)) {
                return null;
            }

            $phones[$key]['label'] = 'phone';
            $phones[$key]['number'] = $number;
        }
        if (empty($phones)) {
            return null;
        }

        return $phones;
    }


    /**
     * @create resource directory fot labour
     * @return labour resource directory id
     */
    private function createResourceDir($subContractor)
    {
        $parentDirId = $this->getRootDir();

        $dirName = $subContractor->first_name . '_' . $subContractor->last_name . '_' . $subContractor->id;
        $subContractorDir = $this->resourceService->createDir($dirName, $parentDirId);

        return $subContractorDir->id;
    }

    /**
     * @create 'labours' directory if not exist
     * @return 'labours' directory Id
     */
    private function getRootDir()
    {
        $scope = App::make(\App\Services\Contexts\Context::class);
        $parentDir = Resource::name(Resource::LABOURS)->company($scope->id())->first();
        if (!$parentDir) {
            $root = Resource::companyRoot($scope->id());
            $parentDir = $this->resourceService->createDir(Resource::LABOURS, $root->id);
        }

        return $parentDir->id;
    }

    /**
     * @ upload Profile Pic of Labour / sub_contractor
     */
    private function uploadProfilePic($data)
    {
        $fileName = $data['labour_id'] . '_' . Carbon::now()->timestamp . '.jpg';
        $baseName = 'company/labours/' . $fileName;
        $fullPath = config('jp.BASE_PATH') . $baseName;
        $image = \Image::make($data['image']);
        if ($image->height() > $image->width()) {
            $image->heighten(200, function ($constraint) {
                $constraint->upsize();
            });
        } else {
            $image->widen(200, function ($constraint) {
                $constraint->upsize();
            });
        }

        FlySystem::put($fullPath, $image->encode()->getEncoded());

        return $baseName;
    }

    /**
     * @ delete Profile Pic of Labour / sub_contractor
     */
    private function removeProfilePic($subContractor)
    {
        $profilePic = $subContractor->profile->profile_pic;
        if (empty($profilePic)) {
            return false;
        }
        $fullPath = config('jp.BASE_PATH') . $profilePic;
        FlySystem::delete($fullPath);

        return true;
    }

    private function saveFinancialDetails($subContractor, $input)
    {
        if (!isset($input['rate_sheet'])) {
            return;
        }

        $input['sub_id'] = $subContractor->id;
        $validator = Validator::make($input, FinancialProduct::getRateSheetRules());

        if ($validator->fails()) {
            return $validator;
        }

        $companyId = $this->repo->getScopeId();
        $category = FinancialCategory::whereCompanyId($companyId)
            ->whereName('LABOR')
            ->first();
        if (!$category) {
            $category = FinancialCategory::create(['company_id' => $companyId, 'name' => 'LABOR']);
        }

        $financialProductRepo = App::make(\App\Repositories\FinancialProductsRepository::class);

        $financialProductRepo->saveOrUpdateSubRateSheet($subContractor->id, $category->id, $input);
    }
}
