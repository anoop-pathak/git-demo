<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\ApiResponse;
use App\Models\JobAwardedStage;
use App\Models\Setting;
use App\Repositories\SettingsRepository;
use Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;
use Settings;
use Illuminate\Support\Facades\Queue;

class SettingsController extends ApiController
{

    protected $repo;
    protected $scope;

    public function __construct(Larasponse $response, SettingsRepository $repo)
    {
        $this->response = $response;
        $this->repo = $repo;

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }

        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     * GET /settings
     *
     * @return Response
     */
    public function index()
    {
        $userId = null;

        if (\Auth::user()->isAuthority()) {
            $userId = Request::get('user_id') ?: null;
        }

        if ($userId) {
            $settings = Settings::forUser($userId)->all();
        } else {
            $settings = Settings::all();
        }

        return ApiResponse::success([
            'data' => $settings
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * POST /settings
     *
     * @return Response
     */
    public function store()
    {
        $input = Request::all();
        $validator = Validator::make($input, Setting::getRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $setting = $this->repo->saveSetting($input);

        if (!$setting) {
            return ApiResponse::errorInternal();
        }

        Queue::push('\App\Handlers\Events\UpdateCompanySettingTimeQueueHandler@updateComapnySettingUpdateTime', [
			'user_id' => $setting->user_id,
			'company_id' => $setting->company_id,
		]);
        return ApiResponse::success([
            'message' => Lang::get('response.success.saved', ['attribute' => 'Setting']),
            'data' => $setting->toArray()
        ]);
    }

    /**
     * Get setting by key
     * Get /settings/by_key/{key}
     *
     * @return Response
     */
    public function get_by_key()
    {

        $userId = null;

        if (\Auth::user()->isAuthority()) {
            $userId = Request::get('user_id') ?: null;
        }

        if ($userId) {
            $setting = Settings::forUser($userId)->getByKey(Request::get('key'));
        } else {
            $setting = Settings::getByKey(Request::get('key'));
        }

        return ApiResponse::success([
            'data' => $setting
        ]);
    }

    /**
     * Get setting list
     * Get /settings/list
     *
     * @return Response
     */
    public function get_list()
    {

        $userId = null;

        if (\Auth::user()->isAuthority()) {
            $userId = Request::get('user_id') ?: null;
        }

        if ($userId) {
            $settings = Settings::forUser($userId)->pluck();
        } else {
            $settings = Settings::pluck();
        }

        // add some additional settings in setting list..
        $settings = $this->addAdditionalSettings($settings);

        return ApiResponse::success([
            'data' => $settings
        ]);
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /settings/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function destroy($id)
    {
        $setting = $this->repo->getById($id);
        $setting->delete();
        return ApiResponse::success([
            'message' => Lang::get('response.success.deleted', ['attribute' => 'Setting'])
        ]);
    }

    /**
     * Check Nearby feature possible or not
     * Get /settings/nearby
     *
     * @return Response
     */
    public function nearby_feature()
    {
        return ApiResponse::success([
            'distance' => Address::isDistanceCalculationPossible()
        ]);
    }

    /**************** Private Settings *******************/
    private function addAdditionalSettings($settings)
    {
        try {
            // set awarded stage in settings for mobile only..
            if (config('is_mobile')) {
                $awardedStage = JobAwardedStage::whereCompanyId(getScopeId())
                    ->whereActive(1)
                    ->first();

                if ($awardedStage) {
                    $settings['JOB_AWARDED_STAGE'] = $awardedStage->stage;
                }
            }

            return $settings;
        } catch (\Exception $e) {
            return $settings;
        }
    }
}
