<?php

namespace App\Services\CompanyCam;

use App\Exceptions\AccessForbiddenException;
use App\Exceptions\InternalServerErrorException;
use App\Exceptions\InvalidRequestException;
use App\Exceptions\NotFoundException;
use App\Exceptions\PaymentRequredException;
use App\Exceptions\AuthorizationException;
use App\Exceptions\UnprocessableEntityException;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\Exceptions\CompanyCamPhotoIdInvalidException;
use Illuminate\Support\Facades\Log;

class CompanyCamConnect
{

    /**
     * Guzzle Http request.
     * @var GuzzleHttp\Client
     */
    protected $request;

    function __construct($accessToken, $username = null)
    {
        $this->request = new Client([
            'base_uri' => config('company-cam.base_uri'),
            'verify' => false,
            'headers' => [
                'Authorization' => "Bearer $accessToken",
                'X-CompanyCam-User' => $username,
                'Content-Type' => 'application/json',
            ]
        ]);
    }

    public function authenticate()
    {
        try {
            $response = $this->request->get('users/current');
            // create dummy project for permission test..
            $project = $this->createProject('Access Token writable permissions test.');
            // delete project..
            $this->deleteProject($project['id']);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            $response = $e->getResponse();

            $statusCode = $response->getStatusCode();

            //Unauthorized
            if ($statusCode == 401) {
                throw new AuthorizationException("Invalid token.", $statusCode);
            }

            return $this->handleRequestException($e);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Create Project
     * @param  string $name | Project name
     * @param  array $address | Address array
     * @param  array $coordinates | Coordinates
     * @return response
     */
    public function createProject($name, $address = [], $coordinates = [])
    {

        $requestBody = [
            'project' => [
                'name' => $name,
            ]
        ];

        if (!empty($address)) {
            $requestBody['project']['address'] = $address;
        }

        if (ine($coordinates, 'lat') && ine($coordinates, 'lon')) {
            $requestBody['project']['coordinates'] = $coordinates;
        }

        try {
            $response = $this->request->post('projects', ['json' => $requestBody]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            return $this->handleRequestException($e);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function deleteProject($projectId)
    {
        $response = $this->request->delete("projects/$projectId");

        return json_decode($response->getBody(), true);
    }

    /**
     * Update Project
     * @param  string $projectId | Project Id
     * @param  string $name | Project name
     * @param  array $address | Address array
     * @param  array $coordinates | Coordinates
     * @return response
     */
    public function updateProject($projectId, $name, $address = [], $coordinates = [])
    {

        $requestBody = [
            'project' => [
                'name' => $name,
                'address' => $address,
                'coordinates' => $coordinates,
            ]
        ];

        try {
            $response = $this->request->put("projects/$projectId", ['json' => $requestBody]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            return $this->handleRequestException($e);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get project photos
     * @param  string $projectId | Project id
     * @param  array $filters | filters array
     * @return response
     */
    public function getProjectPhotos($projectId, $filters = [])
    {
        try {
            $filters = $this->mapFilters($filters);

            $response = $this->request->get("projects/$projectId/photos", ['json' => $filters]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            return $this->handleRequestException($e);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * List All projects
     * @param  array $filters | Filters array
     * @return response
     */
    public function listProjects($filters = [])
    {
        try {
            $filters = $this->mapFilters($filters);

            $response = $this->request->get('projects', ['json' => $filters]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            return $this->handleRequestException($e);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get Single Project By Id
     * @param  string $projectId | Project Id
     * @return response
     */
    public function getSingleProject($projectId)
    {
        try {
            $response = $this->request->get("projects/$projectId");

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            return $this->handleRequestException($e);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get All Company Photos
     * @param  array $filters | Filters array
     * @return response
     */
    public function companyPhotos($filters = [])
    {
        try {
            $filters = $this->mapFilters($filters);

            $response = $this->request->get('photos', ['json' => $filters]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get photo object
     * @param  string $photoId | Photo id
     * @return response
     */
    public function getPhoto($photoId)
    {
        try {
            $response = $this->request->get("photos/$photoId");

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            $getCode = $e->getCode();

			if($getCode == 404) {
				throw new CompanyCamPhotoIdInvalidException("CompanyCam photo Not Found");
			}
            throw $e;
        }
    }

    /************** Private section ****************/

    /**
     * handle request exception
     * @param  Exception $e | Exception object
     * @return void
     */
    private function handleRequestException($e)
    {
        $response = $e->getResponse();

        $statusCode = $response->getStatusCode();

        switch ($statusCode) {
            case 400:
                throw new InvalidRequestException("Invalid request.", $statusCode);
                break;

            case 401:
                throw new AuthorizationException("Invalid access token. Reconnect your account.", $statusCode);
                break;

            case 402:
                throw new PaymentRequredException("Companycam subscription has expired. Reconnect your account.", $statusCode);
                break;

            case 403:
                throw new AccessForbiddenException("Access forbidden.", $statusCode);
                break;

            case 404:
                throw new NotFoundException("Not found.", $statusCode);
                break;

            case 422:
                throw new UnprocessableEntityException("Invalid request data.", $statusCode);
                break;

            case 500:
                throw new InternalServerErrorException("Companycam internal error occured.", $statusCode);
                break;

            default:
                Log::error($e);
                throw new InternalServerErrorException("Companycam internal error occured.", $statusCode);
                break;
        }
    }

    /**
     * Map filters
     * @param  array $filters | Filters array
     * @return array
     */
    private function mapFilters($filters = [])
    {
        $params = [];

        // only active objects..
        $params['status'] = 'active';

        if (ine($filters, 'per_page')) {
            $params['per_page'] = $filters['per_page'];
        }

        if (ine($filters, 'page')) {
            $params['page'] = $filters['page'];
        }

        if (ine($filters, 'project_ids')) {
            $params['project_ids'] = (array)$filters['project_ids'];
        }

        // project name or address
        if (ine($filters, 'query')) {
            $params['query'] = $filters['query'];
        }

        return $params;
    }
}
