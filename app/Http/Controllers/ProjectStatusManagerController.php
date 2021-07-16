<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\ProjectStatusManager;
use Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;

class ProjectStatusManagerController extends ApiController
{

    /**
     * Display a listing of the project status.
     * GET /states
     *
     * @return Response
     */
    public function index()
    {
        $status = ProjectStatusManager::whereCompanyId(config('company_scope_id'))
            ->get();

        return ApiResponse::success(['data' => $status]);
    }

    /**
     *
     * POST /project_status_manager
     *
     * @return Response
     */
    public function store()
    {
        $inputs = Request::onlyLegacy('name');
        $validator = Validator::make($inputs, [
            'name' => 'required|unique:project_status_manager,name,null,id,company_id,' . config('company_scope_id')
        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            $projectStatusManager = ProjectStatusManager::create([
                'name' => $inputs['name'],
                'company_id' => config('company_scope_id')
            ]);

            return ApiResponse::success([
                'message' => Lang::get('response.success.saved', ['attribute' => 'Project status']),
                'project_status' => $projectStatusManager
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    /**
     * update project status manager
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function update($id)
    {
        $inputs = Request::onlyLegacy('name');
        $validator = Validator::make($inputs, [
            'name' => 'required|unique:project_status_manager,name,null,' . $id . ',company_id,' . config('company_scope_id')
        ]);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $projectStatusManager = ProjectStatusManager::whereCompanyId(config('company_scope_id'))
            ->whereId($id)
            ->firstOrFail();
        try {
            $projectStatusManager->update(['name' => $inputs['name']]);

            return ApiResponse::success([
                'message' => Lang::get('response.success.updated', ['attribute' => 'Project status']),
                'project_status' => $projectStatusManager
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    /**
     * Display the specified project status.
     * GET /project_status_manager/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function show($id)
    {
        $projectStatus = ProjectStatusManager::findOrFail($id);

        return ApiResponse::success([
            'data' => $projectStatus
        ]);
    }


    /**
     * Delete /project_status_manager
     * @param  [type] $id [description]
     * @return response
     */
    public function destroy($id)
    {
        $status = ProjectStatusManager::whereId($id)
            ->whereCompanyId(config('company_scope_id'))
            ->findOrFail($id);

        if ($status->delete()) {
            return ApiResponse::success([
                'message' => Lang::get('response.success.deleted', ['attribute' => 'Project status']),
            ]);
        }

        return ApiResponse::errorInternal();
    }
}
