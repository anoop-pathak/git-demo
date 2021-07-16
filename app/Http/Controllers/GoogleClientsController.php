<?php

namespace App\Http\Controllers;

use App\Exceptions\DuplicateGoogleAccountTwoWaySyncing;
use App\Models\ApiResponse;
use App\Models\GoogleClient;
use App\Models\User;
use App\Services\Google\GoogleCalenderSyncService;
use App\Services\Google\GoogleConnectService;
use App\Services\Google\GoogleService;
use Illuminate\Support\Facades\DB;
use Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class GoogleClientsController extends ApiController
{

    protected $googleConnect;
    protected $calenderSyncService;

    public function __construct(
        GoogleConnectService $googleConnect,
        GoogleCalenderSyncService $calenderSyncService,
        GoogleService $googleService
    ) {
        $this->googleConnect = $googleConnect;
        $this->calenderSyncService = $calenderSyncService;
        $this->googleService = $googleService;
        parent::__construct();
    }

    public function connectUserAccount($userId)
    {
        $input = Request::onlyLegacy('scope_calendar_and_tasks', 'scope_drive', 'scope_gmail');

        User::findOrFail($userId);

        $stateData = [
            'user_id' => $userId,
            'scope_calendar_and_tasks' => isTrue($input['scope_calendar_and_tasks']),
            // 'scope_drive' => isTrue($input['scope_drive']),
            // 'scope_gmail' => isTrue($input['scope_gmail']),
        ];

        if (!ine($stateData, 'scope_calendar_and_tasks') && !ine($stateData, 'scope_drive') && !ine($stateData, 'scope_gmail')) {
            return ApiResponse::errorGeneral('Select alteast one scope.');
        }

        return redirect($this->googleConnect->getAuthUrlForCalendarAccess($stateData));
    }

    public function connectCompanyAccount()
    {
        $companyId = getScopeId();
        $setState = json_encode(['company_id' => $companyId]);

        return redirect($this->googleConnect->getAuthUrlForDriveAccess($setState));
    }


    public function get_response()
    {
        DB::beginTransaction();
        try {
            if (Request::has('code')) {
                $this->googleConnect->googleAccountConnect(Request::get('code'), Request::get('state'));
            }
        } catch (\Google_Auth_Exception $e) {
            if ($e->getCode() == 400) {
                return view('google_redirect');
            }
            DB::rollback();

            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        } catch (DuplicateGoogleAccountTwoWaySyncing $e) {
            DB::rollback();

            return view('google.calendar_two_way_syncing_error');
        } catch (\Exception $e) {
            DB::rollback();
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
        DB::commit();

        return view('google_redirect');
    }

    public function disconnect($userId)
    {
        $googleClient = GoogleClient::where('user_id', $userId)->firstOrFail();

        if ($googleClient->channel_id) {
            $this->googleService->deleteCalenderChannel($googleClient);
        }

        $this->googleConnect->accountDisconnnect($googleClient);

        // clear token ..
        $googleClient->token = "";
        $googleClient->save();

        // delete client..
        $googleClient->delete();

        return ApiResponse::success([
            'message' => Lang::get('response.success.account_disconnected')
        ]);
    }

    public function disconnectCompanyAccount()
    {
        $companyId = getScopeId();
        $googleClient = GoogleClient::where('company_id', $companyId)->firstOrFail();

        // clear token ..
        $googleClient->token = "";
        $googleClient->save();

        // delete client..
        $googleClient->delete();
        return ApiResponse::success([
            'message' => Lang::get('response.success.account_disconnected')
        ]);
    }

    public function get_notification()
    {
        Log::info('Google Notification' . Request::header('X-Goog-Channel-ID'));
        $channelId = Request::header('X-Goog-Channel-ID');
        //stop update appointment on google appointment change
        $this->calenderSyncService->sync($channelId);
    }

    public function calendarTwoWaySyncing()
    {
        $inputs = Request::onlyLegacy('active', 'user_id');

        $validator = Validator::make($inputs, ['active' => 'required', 'user_id' => 'required']);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $status = ine($inputs, 'active');

        $googleClient = GoogleClient::where('user_id', $inputs['user_id'])
            ->calendar()
            ->whereNull('company_id')
            ->firstOrFail();
        if ($status) {
            $googleAccount = GoogleClient::where('email', $googleClient->email)
                ->where('user_id', '!=', $googleClient->user_id)
                ->calendar()
                ->first();

            if ($googleAccount) {
                return ApiResponse::errorGeneral('Google Calendar 2-way synching cannot be turned on if a common Google Calendar Account is being used for multiple JobProgress users.');
            }
        }

        if ($status) {
            $message = 'Two way syncing enabled.';
            $this->googleService->calenderIntigration($googleClient);
        } else {
            $message = 'Two way syncing disabled.';
            $this->googleService->deleteCalenderChannel($googleClient);
        }

        $googleClient->save();

        return ApiResponse::success(['message' => $message]);
    }
}
