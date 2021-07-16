<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ApiResponse;
use App\Repositories\ActivityLogsRepository;
use App\Transformers\ActivityLogsTransformer;
use Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;
use App\Exceptions\InvalidDivisionException;

class ActivityLogsController extends ApiController
{

    protected $response;
    protected $repo;

    public function __construct(ActivityLogsRepository $repo, Larasponse $response)
    {
        $this->response = $response;
        $this->repo = $repo;

        parent::__construct();

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
    }

    public function get_logs()
    {
        $input = Request::all();
        try{
            switchDBConnection('mysql2');
            $logs = $this->repo->getActivityLogs($input);
            $limit = Request::get('limit') ? Request::get('limit') : 20;
            $logs = $logs->paginate($limit);
            return ApiResponse::success($this->response->paginatedCollection($logs, new ActivityLogsTransformer));
        } catch(InvalidDivisionException $e){
            return ApiResponse::errorGeneral($e->getMessage());
        }

    }

    public function get_recent_count($lastId)
    {
        $filters = Request::all();
        switchDBConnection('mysql2');
        $count = $this->repo->getRecentActivityCount($lastId, $filters);
        return ApiResponse::success(['count' => $count]);
    }

    public function add_activity()
    {
        $input = Request::all();
        $validator = Validator::make($input, ActivityLog::getCreateRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            $activity = $this->repo->addManualActivity($input['subject'], $input['content'], $input);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }

        return ApiResponse::success([
            'data' => $this->response->item($activity, new ActivityLogsTransformer)
        ]);
    }
}
