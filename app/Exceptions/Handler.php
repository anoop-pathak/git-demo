<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use App\Models\ApiResponse;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use App\Models\QuickBookTask;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        if ($exception instanceof \Illuminate\Auth\AuthenticationException)  {
           return ApiResponse::errorUnauthorized($exception->getMessage());
        }

        if($exception instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            return response()->json([
                'message' => 'Not found'
            ], 404);
        }

        if($exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            $message = class_basename($exception->getModel()).' Not Found';

	        return ApiResponse::errorNotFound($message);
        }
        // return parent::render($request, $exception);

        if($exception instanceof ValidationException) {
            return ApiResponse::requestValidation($exception);
        }

        return ApiResponse::errorInternal(trans('response.error.internal'), $exception);
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }
        return response()->json(
            [
                'errors' => [
                    'status' => 401,
                    'message' => 'Unauthenticated',
                ]
            ], 401
        );
    }
}

Queue::failing(function($connection, $job, $data)
{
    if(ine($data, 'job') && ine($data, 'data')){
        $handlers = ['\App\Services\QuickBooks\QueueHandler\QBBaseTaskHandler', '\App\Services\QuickBooks\QueueHandler\BaseTaskHandler'];

        if(in_array(get_parent_class($data['job']), $handlers)){
            $payload = ine($data['data'], 'payload') ? $data['data']['payload'] : null;

            if($payload){
                $task = QuickBookTask::find($payload['id']);

                if($task && $task->object_id == $payload['object_id']){
                    $task->markFailed('Task Failed due to Fatal Error Exception', $job->attempts());
                }
            }
        }
    }

    $message = ine($data, 'data') ? json_encode($data['data']) : $job->getName();
    Log::error('Queue Failed:'.$message);

    $job->delete();
});
