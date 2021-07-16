<?php

namespace App\Http\Controllers;

use App\Models\AccountManager;
use App\Models\ApiResponse;
use App\Repositories\AccountManagersRepository;
use FlySystem;
use App\Transformers\AccountManagersTransformer;
use Carbon\Carbon;
use Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;
use Sorskod\Larasponse\Larasponse;

class AccountManagersController extends Controller
{

    /**
     * AccountManagers Repo
     * @var \App\Repositories\AccountManagersRepositories
     */
    protected $repo;


    /**
     * Transformer Implementation
     * @var \App\Transformer
     */
    protected $response;

    public function __construct(Larasponse $response, AccountManagersRepository $repo)
    {
        $this->response = $response;
        $this->repo = $repo;
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     * GET /AccountManagers
     *
     * @return Response
     */
    public function index()
    {
        $input = Request::all();

        $accountManagers = $this->repo->getFilteredAccountManagers($input);

        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

        $accountManagers = $accountManagers->paginate($limit);

        return ApiResponse::success($this->response->paginatedCollection($accountManagers, new AccountManagersTransformer));
    }

    public function getList()
    {

        $list = $this->repo->getList();

        return ApiResponse::success(['data' => $list]);
    }

    /**
     * Store a newly created resource in storage.
     * POST /AccountManagers
     *
     * @return Response
     */
    public function store()
    {
        $input = Request::all();

        $validator = Validator::make($input, AccountManager::getRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        if ($accountManager = AccountManager::create($input)) {
            if (!(bool)$input['for_all_trades']) {
                $accountManager->trades()->attach((array)$input['trades']);
            }

            return ApiResponse::success([
                'message' => Lang::get('response.success.saved', ['attribute' => 'Account Manager']),
                'account_manager' => $this->response->item($accountManager, new AccountManagersTransformer),
            ]);
        }

        return ApiResponse::errorInternal();
    }

    /**
     * Display the specified resource.
     * GET /AccountManagers/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function show($id)
    {
        $accountManager = AccountManager::where('id', '=', $id)->with('state', 'trades')->firstOrFail();
        return ApiResponse::success(['data' => $this->response->item($accountManager, new AccountManagersTransformer)]);
    }

    /**
     * Update the specified resource in storage.
     * PUT /AccountManagers/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function update($id)
    {
        $accountManager = AccountManager::findOrFail($id);

        $input = Request::all();

        $validator = Validator::make($input, AccountManager::getUpdateRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        if ($accountManager->update($input)) {
            if (!(bool)$input['for_all_trades']) {
                $accountManager->trades()->detach();
                $accountManager->trades()->attach($input['trades']);
            }
            return ApiResponse::success([
                'message' => Lang::get('response.success.updated', ['attribute' => 'Account Manager']),
                'account_manager' => $this->response->item($accountManager, new AccountManagersTransformer),
            ]);
        }

        return ApiResponse::errorInternal();
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /AccountManagers/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function destroy($id)
    {
        $accountManager = AccountManager::findOrFail($id);
        // $accountManager->trades()->detach();
        if ($accountManager->delete()) {
            return ApiResponse::success([
                'message' => Lang::get('response.success.deleted', ['attribute' => 'Account Manager'])
            ]);
        }
        return ApiResponse::errorInternal();
    }

    /**
     * Verify Account Manager by UUID
     * GET /AccountManagers/verify/{uuid}
     *
     * @param  int $id
     * @return Response
     */
    public function veirfy($uuid)
    {
        try {
            $accountManager = $this->repo->getByUUID($uuid);

            return ApiResponse::success([
                'message' => Lang::get('response.success.account_manager_verifed'),
                'account_manager' => $this->response->item($accountManager, new AccountManagersTransformer),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::errorNotFound('CPA Id # not valid.');
        }
    }

    /**
     * Upload profile picture of Admin Manager
     * Post /account_managers/profile_pic
     *
     * @param  int $id
     * @return Response
     */
    public function upload_image()
    {
        $input = Request::onlyLegacy("account_manager_id", "image");
        $validator = Validator::make($input, AccountManager::getUploadProPicRule());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $accountManager = $this->repo->getByField('id', $input['account_manager_id']);
        $this->deleteProfilePic($accountManager);
        $profilePic = $this->uploadProfilePic($input);
        $accountManager->profile_pic = $profilePic;
        if ($accountManager->save()) {
            return ApiResponse::success([
                'message' => Lang::get('response.success.updated', ['attribute' => ' Profile picture']),
                'data' => [
                    'profile_pic' => FlySystem::publicUrl(config('jp.BASE_PATH') . $profilePic),
                ]
            ]);
        }

        return ApiResponse::errorInternal();
    }

    public function delete_profile_pic()
    {
        $input = Request::onlyLegacy('account_manager_id');
        $validator = Validator::make($input, ['account_manager_id' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $accountManager = $this->repo->getByField('id', $input['account_manager_id']);
        try {
            $this->deleteProfilePic($accountManager);
            $accountManager->profile_pic = null;
            $accountManager->save();
            return ApiResponse::success(['message' => Lang::get('response.success.profile_pic_removed')]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    /********************* Private Function ***********************/

    private function uploadProfilePic($data)
    {
        $filename = $data['account_manager_id'] . '_' . Carbon::now()->timestamp . '.jpg';
        $baseName = 'account_mangers/' . $filename;
        $fullpath = config('jp.BASE_PATH') . $baseName;
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
        FlySystem::put($fullpath, $image->encode()->getEncoded());
        return $baseName;
    }

    private function deleteProfilePic($accountManager)
    {

        if (empty($accountManager->profile_pic)) {
            return;
        }

        $profilePic = $accountManager->profile_pic;
        if (empty($profilePic)) {
            return false;
        }
        $fullpath = config('jp.BASE_PATH') . $profilePic;
        FlySystem::delete($fullpath);
    }
}
