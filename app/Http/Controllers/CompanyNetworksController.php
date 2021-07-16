<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidAttachmentException;
use App\Exceptions\InvalidFileException;
use App\Exceptions\NetworkNotExistException;
use App\Exceptions\VerifySocialTokenException;
use App\Models\ApiResponse;
use App\Models\CompanyNetwork;
use App\Repositories\CompanyNetworksRepository;
use App\Services\SocialNetworks\SocialService;
use App\Transformers\CompanyNetworksTransformer;
use App\Transformers\FacebookPageTransformer;
use Request;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;
use App\Exceptions\LinkedInException;
use App\Exceptions\SocialNetworkException;

class CompanyNetworksController extends ApiController
{

    public function __construct(SocialService $socialService, CompanyNetworksRepository $repo, Larasponse $response)
    {
        $this->socialService = $socialService;
        $this->repo = $repo;
        $this->response = $response;
        parent::__construct();
    }

    public function post()
    {
        $response = [];
        $response['success'] = null;
        $response['error'] = null;
        $input = Request::onlyLegacy('message', 'network', 'attachments');
        $validator = Validator::make($input, CompanyNetwork::getRule());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            set_time_limit(0);
            $this->socialService->postOnNetworks(
                $input['message'],
                $input['network'],
                (array)$input['attachments']
            );
            if ($successMessage = $this->getSuccessMessage()) {
                return ApiResponse::success([
                    'message' => $successMessage
                ]);
            }
            if ($errorMessage = $this->getErrorMessage()) {
                return ApiResponse::errorGeneral($errorMessage);
            }
        } catch(LinkedInException $e){

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(SocialNetworkException $e) {

            return ApiResponse::errorGeneral($e->getMessage());
        } catch (InvalidAttachmentException $e) {

            return ApiResponse::errorGeneral($e->getMessage());
        } catch (InvalidFileException $e) {

            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {

            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    public function network_disconnect($network)
    {
        $company_network = $this->repo->delete('network', $network);
        try {
            return ApiResponse::json([
                'message' => trans("response.success.company_disconnected_from_network", ['network' => $network]),
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal('An internal occur', $e);
        }
    }

    public function network_connect()
    {
        try {
            $input = Request::onlyLegacy('token', 'network');
            $validator = Validator::make($input, CompanyNetwork::getConnectRule());
            if ($validator->fails()) {
                return ApiResponse::validation($validator);
            }
            $token = $this->socialService->extendToken($input['token'], $input['network']);
            $companyNetwork = $this->repo->findBy('network', $input['network']);
            if ($companyNetwork) {
                $companyNetwork->token = $token;
                $companyNetwork->save();
                $message = trans('response.success.updated', ['attribute' => 'Company network']);
            } else {
                $this->repo->save($token, $input['network']);
                $message = trans('response.success.saved', ['attribute' => 'Company network']);
            }
            return ApiResponse::json([
                'message' => $message
            ]);
        } catch(SocialNetworkException $e) {

            return ApiResponse::errorGeneral($e->getMessage());
        } catch (VerifySocialTokenException $e) {

            return ApiResponse::errorUnauthorized($e->getMessage());
        } catch (\Exception $e) {

            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    public function get_network_connected()
    {
        try {
            $networks = [CompanyNetwork::FACEBOOK, CompanyNetwork::LINKEDIN, CompanyNetwork::TWITTER];
            foreach ($networks as $key => $network) {
                $companyNetwork = $this->repo->findBy('network', $network);
                if (!empty($companyNetwork)) {
                    $data = $this->response->item($companyNetwork, new CompanyNetworksTransformer);
                } else {
                    $data = [
                        'network' => $network,
                        'is_connected' => false
                    ];
                }
                $companyNetworks[] = $data;
            }
            return ApiResponse::success([
                'data' => $companyNetworks
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    public function get_pages()
    {
        try {
            $pages = $this->socialService->getPageList();
            return ApiResponse::success($this->response->collection($pages['data'], new FacebookPageTransformer));
        } catch (NetworkNotExistException $e) {
            return ApiResponse::errorUnauthorized($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }


    public function linkedin_login_url()
    {
        try {
            $url = $this->socialService->getLinkedinLoginUrl();
            return redirect($url);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    public function get_linkedin_response()
    {
        if (isset($_REQUEST['error'])) {
            return view('google_redirect');
        }
        try {
            $input = Request::onlyLegacy('state');
            $validator = Validator::make($input, ['state' => 'required']);
            if ($validator->fails() || empty(Request::get('code'))) {
                return true;
            }
            $token = $this->socialService->getLinkedinAccessToken();

            $companyNetwork = CompanyNetwork::where('network', CompanyNetwork::LINKEDIN)
                ->where('company_id', $input['state'])
                ->first();
            if ($companyNetwork) {
                $companyNetwork->token = $token;
                $companyNetwork->save();
            } else {
                $companyNetwork = new CompanyNetwork;
                $companyNetwork->token = $token;
                $companyNetwork->company_id = $input['state'];
                $companyNetwork->network = CompanyNetwork::LINKEDIN;
                $companyNetwork->save();
            }
            return view('google_redirect');
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }


    public function save_page()
    {
        $input = Request::onlyLegacy('page_ids');
        $validator = Validator::make($input, ['page_ids' => 'required|array']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            $pages = $this->socialService->savePages($input['page_ids']);
            if ($pages) {
                return ApiResponse::success([
                    'message' => 'Page saved successfully.'
                ]);
            }

            return ApiResponse::errorInternal();
        } catch(SocialNetworkException $e){

            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {

            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }


    private function getSuccessMessage()
    {
        if (!isset(SocialService::$success)) {
            return false;
        }
        $label = ' Network.';
        if (count(SocialService::$success) > 1) {
            $label = ' Networks.';
        }
        $networkName = str_replace(' ', ' and ', implode(' ', SocialService::$success));
        return "Job successfully shared on " . $networkName . $label;
    }

    private function getErrorMessage()
    {
        if (!isset(SocialService::$error)) {
            return false;
        }
        return SocialService::$error;
    }
}
