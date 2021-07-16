<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Request;
use App\Models\ApiResponse;
use App\Models\Resource;
use Illuminate\Support\Facades\Route;

class SetDateDurationFilter
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        if(!Auth::user()->isStandardUser() || Request::isMethod('get')) return;

        $input = $request->all();
        $ids = Request::get('id');
        $userPermissions = Auth::user()->listPermissions();

        if(in_array('manage_company_files', $userPermissions)
            && in_array('manage_resource_viewer', $userPermissions)
            && in_array('manage_job_directory', $userPermissions) ) {

            return;
        }

        if(ine($input, 'parent_id')) {
            $ids = $input['parent_id'];
        }elseif(ine($input, 'id')) {
            $ids = $input['id'];
        }elseif(ine($input, 'move_to')) {
            $ids = $input['move_to'];
            if(ine($input, 'resource_ids')) {
                $ids = array_merge((array)$ids, (array) $input['resource_ids']);
            }elseif(ine($input, 'resource_id')) {
                $ids = array_merge((array)$ids, (array) $input['resource_id']);
            }
        }elseif(ine($input, 'document_type') && ($input['document_type'] == 'resource')) {
            $ids = $input['document_id'];
        }elseif(ine($input, 'resource_ids')) {
            $ids = $input['resource_ids'];
        }

        $dirActions = [
            'ResourcesController@create_dir',
            'ResourcesController@rename',
            'ResourcesController@remove_dir',
        ];

        if(!in_array('manage_job_directory', $userPermissions) && in_array(Route::currentRouteAction(), $dirActions)) {

            $rootDir = Resource::companyRoot(getScopeId());
            $jobResource = Resource::where('company_id', getScopeId())
                ->where('is_dir', true)
                ->where('path', 'like', "$rootDir->path/".Resource::JOB.'/%')
                ->whereIn('id', (array)$ids)
                ->count();

            if($jobResource) {

                return ApiResponse::errorForbidden();
            }
        }

        if(in_array('manage_company_files', $userPermissions)
            && in_array('manage_resource_viewer', $userPermissions)) return;

        //check company files permissions
        $subscriberResource = Resource::where('company_id', getScopeId())
            ->where('path', 'like', '%/'.Resource::SUBSCRIBER_RESOURCES.'/%')
            ->whereIn('id', (array)$ids)
            ->count();
        if($subscriberResource) {
            if(!in_array('manage_company_files', $userPermissions)) {

                return ApiResponse::errorForbidden();
            }
        }

        //check resource viewer permissions
        $companyId = getScopeId().'_workflow';
        $resourceViewer = Resource::where('company_id', getScopeId())
            ->where('path', 'like', '%/'.$companyId.'/%')
            ->whereIn('id', (array)$ids)
            ->count();
        if($resourceViewer) {
            if(!in_array('manage_resource_viewer', $userPermissions)) {

                return ApiResponse::errorForbidden();
            }
        }
    }
}