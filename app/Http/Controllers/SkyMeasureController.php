<?php

namespace App\Http\Controllers;

use App\Exceptions\SkyMeasureError;
use App\Http\Requests\SkyMeasureSignupRequest;
use App\Models\ApiResponse;
use App\Models\SMOrder;
use App\Models\SMReportFile;
use App\Repositories\SMRepository;
use FlySystem;
use App\Services\SkyMeasure\SkyMeasure;
use App\Services\SkyMeasure\SkyMeasureNotifications;
use App\Transformers\SkyMeasureOrderTransformer;
use Illuminate\Support\Facades\DB;
use Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;

class SkyMeasureController extends ApiController
{

    

    protected $response;
    protected $service;
    protected $repo;

    function __construct(Larasponse $response, SkyMeasure $service, SMRepository $repo)
    {
        $this->response = $response;
        $this->service = $service;
        $this->repo = $repo;

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }

        parent::__construct();
    }

    /**
     * Connect Skymeasure account
     * Post /sm/connect
     *
     * @return Response
     */
    public function connect()
    {
        $input = Request::onlyLegacy('username', 'password');
        
        $validator = Validator::make($input, [
            'username' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        try {
            $token = $this->service->authentication($input['username'], $input['password']);

            $client = $this->repo->saveClient($input['username'], $token);
        } catch (SkyMeasureError $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.something_wrong'), $e);
        }

        return ApiResponse::success(['message' => trans('response.success.connected', ['attribute' => 'SkyMeasure account'])]);
    }

    /**
     * Signup and connect
     * Post /sm/signup
     *
     * @return Response
     */
    public function signupAndConnect(SkyMeasureSignupRequest $request)
    {
        try {
            $client = $this->executeCommand('\App\Commands\SMSignupCommand', $request->all());
        } catch (SkyMeasureError $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.something_wrong'), $e);
        }

        return ApiResponse::success(['message' => trans('response.success.connected', ['attribute' => 'SkyMeasure account'])]);
    }

    /**
     * Disconnect Skymeasure account
     * Delete /sm/disconnect
     *
     * @return Response
     */
    public function disconnect()
    {
        $client = $this->repo->getClient();

        if (!$client) {
            return ApiResponse::errorGeneral(trans('response.error.not_connected', ['attribute' => 'SkyMeasure account']));
        }

        $client->delete();

        return ApiResponse::success(['message' => trans('response.success.disconnected', ['attribute' => 'SkyMeasure account'])]);
    }

    /**
     * Place Order
     * Post /sm/place_order
     *
     * @return Response
     */
    public function placeOrder()
    {
        $client = $this->repo->getClient();
        if (!$client) {
            return ApiResponse::errorGeneral(trans('response.error.not_connected', ['attribute' => 'SkyMeasure account']));
        }

        $input = Request::all();

        $validator = Validator::make($input, SMOrder::getPlaceOrderRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        try {
            $order = $this->executeCommand('\App\Commands\SMPlaceOrderCommand', [
                'input' => $input,
                'smToken' => $client->token,
            ]);
        } catch (SkyMeasureError $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }

        return ApiResponse::success([
            'message' => trans('response.success.order_placed'),
            'order' => $order,
        ]);
    }

    /**
     * List orders
     * Get /sm/orders
     * @return Response
     */
    public function listOrders()
    {
        $input = Request::all();
        $orders = $this->repo->getOrders($input);
        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

        if (!$limit) {
            $orders = $orders->get();

            return ApiResponse::success(
                $this->response->collection($orders, new SkyMeasureOrderTransformer)
            );
        }
        $orders = $orders->paginate($limit);

        return ApiResponse::success(
            $this->response->paginatedCollection($orders, new SkyMeasureOrderTransformer)
        );
    }

    /**
     * Get File
     * Get /sm/get_file/{id}
     * @return Response
     */
    public function getFile($id)
    {
        $file = SMReportFile::findOrFail($id);

        if (empty($file->path)) {
            return ApiResponse::errorNotFound(trans('response.error.not_found', ['attribute' => 'File']));
        }

        $fullPath = config('jp.BASE_PATH') . $file->path;

        return FlySystem::download($fullPath, $file->name);

        // $fileResource = FlySystem::read($fullPath);
        // $response = \response($fileResource, 200);
        // $response->header('Content-Type', $file->mime_type);
        // $response->header('Content-Disposition' ,'attachment; filename="'.$file->name.'"');

        // return $response;
    }

    /******************* Partners Rest Services *********************/

    /**
     * Handle Notifications
     * Post /sm/notifications
     * @return Response
     */
    public function handleNotifications()
    {
        set_time_limit(0);
        $input = Request::onlyLegacy('OrderID', 'StatusCode');
        $validator = Validator::make($input, ['OrderID' => 'required', 'StatusCode' => 'required']);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $order = SMOrder::whereOrderId($input['OrderID'])->firstOrFail();

        DB::beginTransaction();
        try {
            $notification = new SkyMeasureNotifications;
            $notification->handle($order, $input['StatusCode']);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return ApiResponse::errorInternal(trans('response.error.something_wrong'));
        }
        DB::commit();
        return ApiResponse::success();
    }
}
