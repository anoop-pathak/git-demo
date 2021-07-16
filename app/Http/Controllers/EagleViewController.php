<?php

namespace App\Http\Controllers;

use App\Exceptions\AuthenticationFailureException;
use App\Exceptions\EagleviewException;
use App\Exceptions\AuthorizationException;
use App\Http\Requests\EagleviewRequest;
use App\Models\ApiResponse;
use App\Models\EVOrder;
use App\Models\EVReport;
use App\Models\EVStatus;
use App\Repositories\EagleViewRepository;
use App\Services\EagleView\EagleView;
use FlySystem;
use App\Transformers\EagleViewOrdersTransformer;
use App\Transformers\EagleViewReportsTransformer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Log;
use Request;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;
use App\Exceptions\EagleViewNotConnectedException;
use App\Exceptions\ReConnectEagleViewException;

class EagleViewController extends ApiController
{

    /**
     * EagleView Service $service
     * @var EagleView\EagleView
     */
    protected $service;

    /**
     * EagleView Repository $repo
     * @var App\Repositories\EagleViewRepository
     */
    protected $repo;

    function __construct(EagleView $service, EagleViewRepository $repo, Larasponse $response)
    {
        $this->response = $response;
        $this->service = $service;
        $this->repo = $repo;
        parent::__construct();
        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
    }

    public function connect()
    {
        $input = Request::onlyLegacy('username', 'password');
        $validator = Validator::make($input, ['username' => 'required', 'password' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            $authenticated = $this->service->authentication($input['username'], $input['password']);
            // if(!$authenticated) {
            //  return ApiResponse::errorInternal(trans('response.error.eagleview_fail_to_connect'));
            // }
            
            $this->repo->saveClient($input['username'], $authenticated['access_token'], $authenticated['refresh_token'], $authenticated['as:client_id'], $authenticated['.expires']);
        } catch (AuthenticationFailureException $e) {
            return ApiResponse::errorGeneral(trans('response.error.invalid', ['attribute' => 'username or password']));
        } catch (EagleviewException $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
        return ApiResponse::success(['message' => trans('response.success.eagleview_connected')]);
    }

    public function disconnect()
    {
        try {
            $this->repo->deleteClient();
            return ApiResponse::success(['message' => trans('response.success.eagleview_disconnected')]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal();
        }
    }

    public function get_products()
    {
        $input = Request::onlyLegacy('refresh_products');
        $evClient = $this->repo->getClient();
        $username = $evClient->username;
        $cacheKey = getScopeId() . 'eagle_view_products';

        if (ine($input, 'refresh_products')) {
            Cache::forget($cacheKey);
        }

        try {
            if (Cache::has($cacheKey)) {
                $response = Cache::get($cacheKey);
            } else {
                $response = $this->service->getAllProducts();
                Cache::put($cacheKey, $response, config('jp.ev_cache_expiry_time'));
            }
            return ApiResponse::success(['data' => $response]);
        } catch (AuthorizationException $e) {
            return ApiResponse::errorGeneral($e->getMessages());
        } catch(ReConnectEagleViewException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch(EagleViewNotConnectedException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch(EagleviewException $e) {
            return ApiResponse::errorInternal($e->getMessage(), $e);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    public function get_measurments()
    {
        $measurments = DB::table('ev_measurments')->select('id', 'name')->get();
        return ApiResponse::success([
            'data' => $measurments
        ]);
    }

    public function place_order(EagleviewRequest $request)
    {
        $data = $request->all();
        try {
            $order = $this->executeCommand('\App\Commands\EVOrderCommand', $data);
        } catch (AuthorizationException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch(ReConnectEagleViewException $e){
            return ApiResponse::errorGeneral($e->getMessage());
        } catch(EagleViewNotConnectedException $e){
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (EagleviewException $e) {
            return ApiResponse::errorGeneral($e->getMessage(), $e);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
        return ApiResponse::success([
            'message' => trans('response.success.eagle_view_order_placed'),
            'order' => $this->response->item($order, new EagleViewOrdersTransformer)
        ]);
    }

    public function list_orders()
    {
        $orders = $this->repo->getOrders(Request::all());
        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

        if (!$limit) {
            $orders = $orders->get();
            return ApiResponse::success($this->response->collection($orders, new EagleViewOrdersTransformer));
        }
        $orders = $orders->paginate($limit);
        return ApiResponse::success($this->response->paginatedCollection($orders, new EagleViewOrdersTransformer));
    }

    public function get_report_files()
    {
        $input = Request::onlyLegacy('job_id');
        $validator = Validator::make($input, ['job_id' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        //check job exists..
        $reports = $this->repo->getReportsByJob($input['job_id']);
        return ApiResponse::success($this->response->collection($reports, new EagleViewReportsTransformer));
    }

    public function get_file($id)
    {
        $file = EVReport::findOrFail($id);

        if (empty($file->file_path)) {
            return ApiResponse::errorNotFound(trans('response.error.not_found', ['attribute' => 'File']));
        }
        $fullPath = config('jp.BASE_PATH') . $file->file_path;

        return FlySystem::download($fullPath, $file->file_name);

        // $fileResource = FlySystem::read($fullPath);
        // $response = \response($fileResource, 200);
        // $response->header('Content-Type', $file->file_mime_type);
        // $response->header('Content-Disposition' ,'filename="'.$file->file_name.'"');
        // return $response;
    }

    public function get_status_list()
    {
        $statuses = EVStatus::select('id', 'name')->get();
        return ApiResponse::success([
            'data' => $statuses
        ]);
    }

    /**
     * @method  return unique product list from orders table for filters
     * @return [array] [list]
     */

    public function get_product_list()
    {
        $list = $this->repo->getProductListFromOrders();
        return ApiResponse::success([
            'data' => $list
        ]);
    }

    /**********************Partners Rest Services*****************************/

    public function order_status_update()
    {
        $input = Request::onlyLegacy('StatusId', 'SubStatusId', 'RefId', 'ReportId');
        $validator = Validator::make($input, EVOrder::getStatusUpdateRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $order = $this->repo->getOrderByReportId($input['ReportId']);
        try {
            $this->repo->updateOrderStatus($order, $input['StatusId'], $input['SubStatusId']);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal();
        }
        return ApiResponse::success();
    }

    public function file_delivery_confirmation()
    {
        $input = Request::onlyLegacy('RefId', 'ReportId');
        $validator = Validator::make($input, ['ReportId' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $this->repo->getOrderByReportId($input['ReportId']);
        return ApiResponse::success();
    }

    public function file_delivery()
    {
        $input = Request::all();

        $validator = Validator::make($input, EVReport::getFileDeliveryRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $order = $this->repo->getOrderByReportId($input['ReportId']);
        $contents = Request::getContent();

        DB::beginTransaction();
        try {
            //Write data to file
            $ext = config('eagleview.file_formates')[$input['FileFormatId']];
            $fileName = $order->report_id . $ext;
            $basePath = 'eagle_view_reports/' . $fileName;
            $fullPath = config('jp.BASE_PATH') . $basePath;
            $fileMimeType = Request::header('Content-Type');

            FlySystem::put($fullPath, base64_decode($contents), ['ContentType' => $fileMimeType]);

            $fileSize = FlySystem::getSize($fullPath);

            //save report data
            $this->repo->saveReport($input['ReportId'], $input['FileTypeId'], $fileName, $basePath, $fileSize, $fileMimeType);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('EagleView File Delivery :' . $e->getMessage());
            throw $e;
        }
        DB::commit();

        return ApiResponse::success();
    }

    /**
    * get ev report by report id
    *
    * @return response
    */
    public function getReportById()
    {
        $input = Request::onlyLegacy('report_id');
        $validator = Validator::make($input,['report_id' => 'required']);
        if($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            $report = $this->service->getReportById($input['report_id']);
        } catch(\Exception $e) {
            throw $e;
        }
        return $report;
    }
    public function renewToken()
    {
        DB::beginTransaction();
        try {
            $evClient = $this->repo->getClient();
            $renew = $this->service->renewToken($evClient->refresh_token);
            \Log::info($renew['as:client_id']);
            if($renew) {
                $this->repo->saveClient($evClient->username, $renew['access_token'], $renew['refresh_token'], $renew['as:client_id'], $renew['.expires']);
            }else {
                return ApiResponse::errorInternal(trans('response.error.eagleview_fail_to_connect'));
            }
        } catch(\Exception $e) {
            DB::rollback();
            \Log::error($e->getMessage());
            throw $e;
        }
        DB::commit();
        
        return $renew;
    }
    public function premiumReport()
    {
        $content = FlySystem::read(config('jp.BASE_PATH').'eagle_view_reports/13107836.json');
        $reportData = json_decode($content, true);
        $faces = $reportData['EAGLEVIEW_EXPORT']['STRUCTURES']['ROOF']['FACES']['FACE'];
        $totalFaces = array_count_values(array_column($faces, '@type'));
        $lines = $reportData['EAGLEVIEW_EXPORT']['STRUCTURES']['ROOF']['LINES']['LINE'];
        $points = $reportData['EAGLEVIEW_EXPORT']['STRUCTURES']['ROOF']['POINTS']['POINT'];
        $pointsData = array_column($points, '@data', '@id');
        $type = $data = [];
        $attributes =  [];
        foreach ($lines as $key => $line) {
            list($x, $y) = explode(',', $line['@path']);
            list($x1, $y1, $z1) = explode(',', $pointsData[$x]);
            list($x2, $y2, $z2) = explode(',', $pointsData[$y]);
            $value = sqrt(pow($x2 - $x1, 2) + pow($y2 - $y1, 2) + pow($z2 - $z1, 2));
            $lines[$key][$line['@type']] = $value;
            $attributeData = $lines;
        }
        $pitchValues = [];
        $size = [];
        $roofArea = [];
        $wallArea = [];
        foreach ($faces as $key => $face) {
            // $pitchValues[] = $face['POLYGON']['@pitch'];
            if($face['POLYGON']['@pitch'] != 'Infinity'){
                $pitchValues = $face['POLYGON']['@pitch'];
            }
            if($face['@type'] == 'WALLPENETRATION' || $face['@type'] == 'WALL') continue;
            $size[] = $face['POLYGON']['@unroundedsize'];
            $facePaths[$face['@id']] = explode(',', $face['POLYGON']['@path']);
        }
        $measurementAttributes = ['EAVE', 'HIP', 'RIDGE', 'VALLEY', 'RAKE', 'FLASHING', 'STEPFLASH'];
        foreach ($measurementAttributes as $key => $mAttribute) {
            $attributes[$mAttribute] = round(array_sum(array_column($lines, $mAttribute)));
        }
        $attributes['area'] = array_sum($size);
        // $attributes['pitch'] = array_sum($pitchValues) / $facets .'/12';
        $attributes['pitch'] = $pitchValues.'/12';
        $attributes['facets'] = $totalFaces['ROOF'];
    }
}
