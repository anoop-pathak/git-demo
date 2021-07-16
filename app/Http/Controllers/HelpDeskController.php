<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\Company;
use App\Models\User;
use App\Services\Zendesk\ZendeskService;
use Illuminate\Support\Facades\Auth;
use Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;

class HelpDeskController extends ApiController
{

    protected $service;

    public function __construct(ZendeskService $service)
    {
        $this->service = $service;
    }

    public function remote_login()
    {
        try {
            $user = \Auth::user(); // current user
            $location = $this->service->authentication($user);
            return redirect($location);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    public function connect_company($companyId)
    {
        try {
            $company = Company::findOrFail($companyId);
            $organization = $this->service->addOrganization($company);
            $company->zendesk_id = $organization->id;
            $company->save();
            return ApiResponse::success([
                'message' => Lang::get('response.success.connected_to_zendesk', ['attribute' => 'Company'])
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal();
        }
    }

    public function connect_user($userId)
    {
        try {
            $user = User::findOrFail($userId);
            if (isset($user->company->zendesk_id) && !empty($user->company->zendesk_id)) {
                $organizationId = $user->company->zendesk_id;
            } else {
                $organizationId = null;
            }

            $role = Request::get('role');
            if (!$role) {
                $role = null;
            }
            $company = $user->company;
            $zendeskUser = $this->service->addUser($user, $organizationId, $role);
            $user->zendesk_id = $zendeskUser->id;
            $user->save();
            return ApiResponse::success([
                'message' => Lang::get('response.success.connected_to_zendesk', ['attribute' => 'User'])
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal();
        }
    }

    public function create_ticket()
    {
        $input = Request::onlyLegacy('subject', 'content', 'attachments');
        $validator = Validator::make($input, [
            'subject' => 'required',
            'content' => 'required',
            'attachments' => 'array',
        ]);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $user = \Auth::user();
        try {
            $ticket = $this->service->createTicket($user, $input['subject'], $input['content'], $input['attachments']);
            return ApiResponse::success([
                'message' => trans('response.success.submitted', ['attribute' => 'Request']),
                'ticket' => $ticket->id,
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    public function request_attachment()
    {
        $input = Request::onlyLegacy('file');
        $validFiles = implode(',', array_merge(config('resources.image_types'), config('resources.docs_types')));
        $validator = Validator::make($input, ['file' => 'required|mime_types:' . $validFiles]);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        try {
            $token = $this->service->uploadfile($input['file']);
            return ApiResponse::success([
                'token' => $token,
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    public function delete_attachment()
    {
        $input = Request::onlyLegacy('token');
        $validator = Validator::make($input, ['token' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        try {
            $token = $this->service->deleteAttachment($input['token']);
            return ApiResponse::success([
                'message' => trans('response.success.removed', ['attribute' => 'Attachment']),
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }
}
