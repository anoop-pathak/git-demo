<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\MobileApp;
use App\Transformers\MobileAppsTransformer;
use Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;

class MobileAppsController extends Controller
{


    public function __construct(Larasponse $response)
    {
        $this->response = $response;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $input = Request::all();
        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');
        try {
            $mobileApp = MobileApp::orderBy('id', 'desc')->paginate($limit);
            return ApiResponse::success($this->response->paginatedCollection($mobileApp, new MobileAppsTransformer));
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store()
    {
        $input = Request::all();
        $validator = Validator::make($input, MobileApp::getRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $input = $this->setUrl($input);
        if (MobileApp::create($input)) {
            return ApiResponse::success([
                'message' => Lang::get('response.success.saved', ['attribute' => 'Mobile app'])
            ]);
        }
        return ApiResponse::errorInternal();
    }


    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return Response
     */
    public function show($id)
    {
        $mobileApp = MobileApp::findOrFail($id);
        try {
            return ApiResponse::success([
                'data' => $this->response->item($mobileApp, new MobileAppsTransformer)
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  int $id
     * @return Response
     */
    public function update($id)
    {
        $input = Request::all();
        $validator = Validator::make($input, MobileApp::getRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $mobileApp = MobileApp::findOrFail($id);
        $input = $this->setUrl($input);
        if ($mobileApp->update($input)) {
            return ApiResponse::success([
                'message' => Lang::get('response.success.updated', ['attribute' => 'Mobile app'])
            ]);
        }
        return ApiResponse::errorInternal();
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return Response
     */
    public function destroy($id)
    {
        $mobileApp = MobileApp::findOrFail($id);
        if ($mobileApp->delete()) {
            return ApiResponse::success([
                'message' => Lang::get('response.success.deleted', ['attribute' => 'Mobile app'])
            ]);
        }
        return ApiResponse::errorInternal();
    }

    /**
     * get latest mobile app.
     *
     * @param  string $device
     * @return Response
     */
    public function get_latest_mobile_app()
    {
        $input = Request::onlyLegacy('device');
        $validator = Validator::make($input, ['device' => 'required|in:ios,android']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $mobileApp = MobileApp::orderBy('created_at', 'desc')
            ->whereDevice($input['device'])
            ->first();
        try {
            return ApiResponse::success([
                'data' => !empty($mobileApp) ? $this->response->item($mobileApp, new MobileAppsTransformer) : []
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    /**
     * Mobile app approvel
     *
     * @param  int $id
     * @return Response
     */
    public function approval($id)
    {
        $mobileApp = MobileApp::findOrFail($id);
        $input = Request::onlyLegacy('approved');
        $validator = Validator::make($input, ['approved' => 'required|boolean']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            $mobileApp->approved = $input['approved'];
            $mobileApp->save();
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
        if ($input['approved']) {
            return ApiResponse::success(['message' => Lang::get('response.success.mobile_app_approved')]);
        } else {
            return ApiResponse::success(['message' => Lang::get('response.success.mobile_app_disproved')]);
        }
    }


    /************************* private function ***********************************/
    private function setUrl($input)
    {
        if (!ine($input, 'url') && ($input['device'] == MobileApp::IOS)) {
            $input['url'] = config('jp.ios_app_url');
        }
        if (!ine($input, 'url') && ($input['device'] == MobileApp::ANDROID)) {
            $input['url'] = config('jp.android_app_url');
        }
        return $input;
    }
}
