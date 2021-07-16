<?php

namespace App\Http\Controllers;

use App\Exceptions\DropboxAccountNotConnectedException;
use App\Exceptions\DropboxException;
use App\Exceptions\DropboxFileSizeExceededException;
use App\Models\ApiResponse;
use App\Models\DropboxClient;
use App\Repositories\DropboxRepository;
use App\Services\Contexts\Context;
use App\Services\DropBox\DropboxService;
use App\Transformers\DropboxTransformer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Request;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;

class DropboxController extends ApiController
{

    protected $response;
    protected $service;
    protected $repo;
    protected $scope;

    function __construct(Larasponse $response, DropboxService $service, DropboxRepository $repo, Context $scope)
    {
        $this->response = $response;
        $this->service = $service;
        $this->repo = $repo;
        $this->scope = $scope;

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }

        parent::__construct();
    }

    /**
     * Connect Dropbox account
     * Post /sm/connect
     *
     * @return Response
     */
    public function connect()
    {

        try {
            return $this->service->authentication();
        } catch (DropboxException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.something_wrong'), $e);
        }

        return ApiResponse::success([
            'message' => trans('response.success.connected', ['attribute' => 'Dropbox account']),
            'data' => $url
        ]);
    }

    public function response()
    {
        $input = Request::all();
        try {
            $state = json_decode($input['state']);
            $response = $this->service->generateTokenByCode($input['code']);
            $companyId = Crypt::decrypt($state->company_id);
            $userId = Crypt::decrypt($state->user_id);

            $this->repo->save($companyId, $userId, $response['data']['access_token'], $response['data']['uid'], $response['data']['account_id'], $response['user_data']['email']);

            return view('google_redirect');
        } catch (DropboxException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Get file list
     * GET /dropbox/list
     *
     * @return Response
     */
    public function getList()
    {
        try {
            $input = Request::all();
            $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');
            $token = $this->repo->token(\Auth::id());
            $response = $this->service->getList($token, $limit, $input);
            // dd($response['has_more']);
            $data = $this->response->collection($response['entries'], new DropboxTransformer);

            $data['meta']['next_page_token'] = ine($response, 'has_more') ? $response['cursor'] : null;

            return ApiResponse::success($data);
        } catch (DropboxAccountNotConnectedException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (DropboxException $e) {
            if ($e->getCode() == 400) {
                return ApiResponse::errorBadRequest($e->getMessage());
            }
            if ($e->getCode() == 409) {
                return ApiResponse::errorNotFound($e->getMessage());
            }
            if ($e->getCode() == 403) {
                return ApiResponse::errorForbidden($e->getMessage());
            }

            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Get / Download file
     * GET /dropbox/download
     *
     * @return Response
     */
    public function downloadFile()
    {
        $input = Request::all();

        $validator = Validator::make($input, ['file_id' => 'required']);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        try {
            $token = $this->repo->token(\Auth::id());
            $file = $this->service->download($token, $input);
            // $file = $this->service->download($token, $input['file_id']);
            $response = \response($file['content'], 200);
            $response->header('Content-Disposition', 'attachment; filename="' . $file['file']['name'] . '"');

            return $response;
        } catch (DropboxAccountNotConnectedException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (DropboxFileSizeExceededException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (DropboxException $e) {
            if($e->getCode() == 503){
                return view('dropbox.service_unavailable_error');
            }
            if ($e->getCode() == 400) {
                return ApiResponse::errorBadRequest($e->getMessage());
            }
            if ($e->getCode() == 409) {
                return view('dropbox.service_unavailable_error');
            }
            if ($e->getCode() == 403) {
                return ApiResponse::errorForbidden($e->getMessage());
            }

            return ApiResponse::errorGeneral($e->getMessage());
        } catch(DropboxAccountNotConnectedException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.something_wrong'), $e);
        }
    }

    /**
     * Save/Copy DropBox file
     * POST - /dropbox/save_file
     *
     * @return Response
     */
    public function saveFile()
    {
        try {
            $input = Request::onlyLegacy('file_id', 'save_as', 'parent_id', 'job_id');

            $validator = Validator::make($input, [
                'file_id' => 'required',
                'save_as' => 'required|in:resource,proposal,estimate',
                'job_id' => 'required_if:save_as,proposal,estimate',
                'parent_id' => 'required_if:save_as,resource',
            ]);

            if ($validator->fails()) {
                return ApiResponse::validation($validator);
            }
            $token = $this->repo->token(\Auth::id());

            $file = $this->service->saveFile($token, $input['file_id'], $input);

            return ApiResponse::success([
                'message' => trans('response.success.saved', ['attribute' => 'File']),
                'data' => $this->response->item($file['dataObject'], $file['transformer'])
            ]);
        } catch (DropboxAccountNotConnectedException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (DropboxFileSizeExceededException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (DropboxException $e) {
            if ($e->getCode() == 400) {
                return ApiResponse::errorBadRequest($e->getMessage());
            }
            if ($e->getCode() == 409) {
                return ApiResponse::errorNotFound($e->getMessage());
            }
            if ($e->getCode() == 403) {
                return ApiResponse::errorForbidden($e->getMessage());
            }

            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Disconnect Dropbox Account
     * DELETE - /dropbox/disconnect
     *
     * @return Response
     */
    public function disconnect()
    {
        try {
            $dropbox = DropboxClient::whereCompanyId($this->scope->id())->firstOrFail();
            $dropbox->delete();

            return ApiResponse::success([
                'message' => trans('response.success.disconnected', ['attribute' => 'DropBox Account']),
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * search files and folders
     * GET - /dropbox/files/search
     *
     * @return response
     */
    public function search()
    {
        $input = Request::onlyLegacy('keyword', 'parent', 'next_page_token');

        try {
            $token = $this->repo->token(\Auth::id());
            $response = $this->service->search($token, $input);

            $data = $this->response->collection($response['data'], new DropboxTransformer);
            $data['meta']['next_page_token'] = $response['next_page_token'];

            return ApiResponse::success($data);
        } catch (DropboxException $e) {
            if ($e->getCode() == 400) {
                return ApiResponse::errorBadRequest($e->getMessage());
            }
            if ($e->getCode() == 409) {
                return ApiResponse::errorNotFound($e->getMessage());
            }
            if ($e->getCode() == 403) {
                return ApiResponse::errorForbidden($e->getMessage());
            }

            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * display listing of shared folders
     * GET - /dropbox/shared_folders
     *
     * @return response
     */
    public function listSharedFolders()
    {
        try {
            $token = $this->repo->token(\Auth::id());
            $response = $this->service->listSharedFolders($token);
        } catch (DropboxException $e) {
            if ($e->getCode() == 400) {
                return ApiResponse::errorBadRequest($e->getMessage());
            }
            if ($e->getCode() == 409) {
                return ApiResponse::errorNotFound($e->getMessage());
            }
            if ($e->getCode() == 403) {
                return ApiResponse::errorForbidden($e->getMessage());
            }

            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }

        return ApiResponse::success($this->response->collection($response, new DropboxTransformer));
    }

    /**
     * display shared files list
     * GET - /dropbox/shared_files
     *
     * @return response
     */
    public function listSharedFiles()
    {
        $input = Request::all();
        try {
            $token = $this->repo->token(\Auth::id());
            $response = $this->service->listSharedFiles($token, $input);
            $files = $this->response->collection($response['entries'], new DropboxTransformer);

            $files['meta']['next_page_token'] = $response['next_page_token'];
        } catch (DropboxException $e) {
            if ($e->getCode() == 400) {
                return ApiResponse::errorBadRequest($e->getMessage());
            }
            if ($e->getCode() == 409) {
                return ApiResponse::errorNotFound($e->getMessage());
            }
            if ($e->getCode() == 403) {
                return ApiResponse::errorForbidden($e->getMessage());
            }

            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }

        return ApiResponse::success($files);
    }

    /**
     * add shared folders to dropbox
     * POST - /dropbox/shared_folders/add_to_dropbox
     *
     * @return response
     */
    public function mountSharedFolders()
    {
        $input = Request::all();
        $validator = Validator::make($input, ['folder_id' => 'required']);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            $token = $this->repo->token(\Auth::id());
            $response = $this->service->mountSharedFolders($token, $input);
        } catch (DropboxException $e) {
            if ($e->getCode() == 400) {
                return ApiResponse::errorBadRequest($e->getMessage());
            }
            if ($e->getCode() == 409) {
                return ApiResponse::errorNotFound($e->getMessage());
            }
            if ($e->getCode() == 403) {
                return ApiResponse::errorForbidden($e->getMessage());
            }

            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }

        return ApiResponse::success(['message' => 'This folder has been successfully added to your Dropbox.']);
    }

    /**
     * download shared files
     * @return response
     */
    // public function downloadSharedFiles()
    // {
    // 	$input    = Request::all();
    // 	$token 	  = $this->repo->token(\Auth::id());
    // 	$file = $this->service->downloadSharedFiles($token, $input);

    // 	$response = \response($file['content'], 200);
    // 	$response->header('Content-Disposition' ,'attachment; filename="'.$file['file']['name'].'"');

    // 	return $response;
    // }
}
