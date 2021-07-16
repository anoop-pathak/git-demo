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
use App\Transformers\LabourTransformer;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Request;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;

class LaboursController extends ApiController
{
    

    /* Larasponse class Instance */
    protected $response;

    /* Labour Repository */
    protected $repo;

    /* ResourceServices class instance */
    protected $resourceService;
    protected $authService;

    public function __construct(
        LabourRepository $repo,
        Larasponse $response,
        ResourceServices $resourceService,
        AuthenticationService $authenticationService
    ) {

        $this->repo = $repo;
        $this->response = $response;
        $this->resourceService = $resourceService;
        $this->authService = $authenticationService;
        parent::__construct();

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
    }

    /**
     * Display a listing of the resource.
     * GET /labours
     *
     * @return Response
     */
    public function index()
    {
        $input = Request::all();

        $labours = $this->repo->getLabours($input);

        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

        if (!$limit) {
            $labours = $labours->get();

            return ApiResponse::success($this->response->collection($labours, new LabourTransformer));
        }
        $labours = $labours->paginate($limit);

        return ApiResponse::success($this->response->paginatedCollection($labours, new LabourTransformer));
    }

    /**
     * Store a newly created resource in storage.
     * POST /labours
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
            $labour = $this->executeCommand('\App\Commands\UserCreateCommand', $input);

            // assign trades
            if (ine($input, 'trades')) {
                $labour = $this->repo->assignTrades($labour, (array)$input['trades']);
            }

            // assign worktype
            if (ine($input, 'work_types')) {
                $labour = $this->repo->assigWorkTypes($labour, (array)$input['work_types']);
            }

            // save sub contractor rate sheet
            if (isset($input['rate_sheet'])) {
                $financial = $this->saveFinancialDetails($labour, $input);

                if ($financial instanceof CustomValidationRules) {
                    return ApiResponse::validation($financial);
                }
            }

            $resourceId = $this->createResourceDir($labour);
            $labour->resource_id = $resourceId;
            $labour->save();
        } catch (\Exception $e) {
            DB::rollback();
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
        DB::commit();

        $type = $this->getLabourTypeForMsg($labour->group_id);
        return ApiResponse::success([
            'message' => trans('response.success.saved', ['attribute' => $type]),
            'data' => $this->response->item($labour, new LabourTransformer),
        ]);
    }

    /**
     * Display the specified resource.
     * GET /labours/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function show($id)
    {
        $labour = $this->repo->getById($id);

        return ApiResponse::success([
            'data' => $this->response->item($labour, new LabourTransformer)
        ]);
    }

    /**
     * Update the specified resource in storage.
     * PUT /labours/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function update($id)
    {
        $labour = $this->repo->getById($id);
        $input = Request::all();

        $input['group_id'] = $labour->group_id;
        $oldGroup = $labour->group_id;
        $rules = array_merge(User::getNonLoggableUserRules(), UserProfile::getCreateRules());
        $rules['rate_sheet'] = 'array';
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $input['id'] = $id;

        DB::beginTransaction();
        try {
            $labour = $this->executeCommand('\App\Commands\UserUpdateCommand', $input);

            if (ine($input, 'trades')) {
                $labour = $this->repo->assignTrades($labour, (array)$input['trades']);
            }

            if (ine($input, 'work_types')) {
                $labour = $this->repo->assigWorkTypes($labour, (array)$input['work_types']);
            }
        } catch (\Exception $e) {
            DB::rollback();
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
        DB::commit();
        $type = $this->getLabourTypeForMsg($labour->group_id);

        return ApiResponse::success([
            'message' => trans('response.success.updated', ['attribute' => $type]),
            'data' => $this->response->item($labour, new LabourTransformer),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /labours/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function destroy($id)
    {
        $labour = $this->repo->getById($id);
        try {
            $type = $this->getLabourTypeForMsg($labour->group_id);
            $labour->financialDetails()->delete();
            // logout from all devices
            $this->authService->logoutFromAllDevices($labour->id);
            $labour->delete();

            return ApiResponse::success([
                'message' => trans('response.success.deleted', ['attribute' => $type]),
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
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
                $labour = $this->executeCommand('\App\Commands\UserCreateCommand', $record);
                $resourceId = $this->createResourceDir($labour);
                $labour->resource_id = $resourceId;
                $labour->save();
            }

            return ApiResponse::success([
                'message' => trans('response.success.imported', ['attribute' => Labour::LABOUR]),
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

        $labour = $this->repo->getById($input['labour_id']);
        try {
            $this->removeProfilePic($labour);
            $profilePic = $this->uploadProfilePic($input);
            $labour->profile->profile_pic = $profilePic;
            $labour->profile->save();

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

        $labour = $this->repo->getById($input['labour_id']);
        try {
            $this->removeProfilePic($labour);
            $labour->profile->profile_pic = null;
            $labour->profile->save();

            return ApiResponse::success(['message' => trans('response.success.profile_pic_removed')]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * @ Activate - Deactivate Labour or sub_contractor
     */
    public function activateLabours()
    {
        $input = Request::onlyLegacy('labour_ids', 'is_active');

        $validator = Validator::make($input, Labour::getActivateDeactivateRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            $this->repo->make()->onlySubContractors()
                ->whereIn('id', (array)$input['labour_ids'])
                ->update(['active' => $input['is_active']]);

            return ApiResponse::success([
                'message' => trans('response.success.updated', ['attribute' => 'Labours']),
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Update the note for labour
     * PUT /labours/{id}/update_note
     *
     * @param  int : $id | string : note
     * @return Response
     */
    public function updateNote($id)
    {
        $labour = $this->repo->getById($id);
        $input = Request::onlyLegacy('note');
        $labour->note = $input['note'];
        if ($labour->save()) {
            return ApiResponse::success([
                'message' => trans('response.success.updated', ['attribute' => 'Labor note']),
            ]);
        }

        return ApiResponse::errorInternal(trans('response.error.internal'));
    }

    /**
     * Update the rating for labour
     * PUT /labours/{id}/rating
     *
     * @param  int : $id | integer : rating
     * @return Response
     */
    public function updateRating($id)
    {
        $input = Request::onlyLegacy('rating');

        //find labour
        $labour = $this->repo->getById($id);

        $validator = Validator::make($input, ['rating' => 'numeric|min:0|max:5']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        //set rating
        $labour->rating = $input['rating'];

        if ($labour->save()) {
            return ApiResponse::success([
                'message' => trans('response.success.updated', ['attribute' => 'Labor rating']),
            ]);
        }

        return ApiResponse::errorInternal(trans('response.error.internal'));
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
    private function createResourceDir($labour)
    {
        $parentDirId = $this->getRootDir();

        $dirName = $labour->first_name . '_' . $labour->last_name . '_' . $labour->id;
        $labourDir = $this->resourceService->createDir($dirName, $parentDirId);

        return $labourDir->id;
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
    private function removeProfilePic($labour)
    {
        $profilePic = $labour->profile->profile_pic;
        if (empty($profilePic)) {
            return false;
        }
        $fullPath = config('jp.BASE_PATH') . $profilePic;
        FlySystem::delete($fullPath);

        return true;
    }

    /**
     *@ return type of labour to show Message (Notification)
     */
    private function getLabourTypeForMsg($type)
    {
        if ($type == User::GROUP_SUB_CONTRACTOR) {
            return 'Sub Contractor';
        }
        return 'Labor';
    }

    private function saveFinancialDetails($labour, $input)
    {
        if (!isset($input['rate_sheet'])) {
            return;
        }

        $input['sub_id'] = $labour->id;
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

        $financialProductRepo->saveOrUpdateSubRateSheet($labour->id, $category->id, $input);
    }
}
