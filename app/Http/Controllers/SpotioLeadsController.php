<?php

namespace App\Http\Controllers;

use Request;
use App\Models\ApiResponse;
use Sorskod\Larasponse\Larasponse;
use App\Services\Spotio\SpotioLeadService;
use App\Repositories\SpotioLeadRepository;
use App\Services\Spotio\Entity\SpotioLeadEntity;
use App\Services\Spotio\Entity\CustomerLeadEntity;
use App\Transformers\SpotioLeadTransformer;
use Exception;

class SpotioLeadsController extends ApiController
{
    /**
     * Response
     * @var Larasponse
     */
    protected $response;

    /**
     * Spotio Leads Service instance
     * @var App\Services\Spotio\SpotioLeadService;
     */
    protected $service;

    /**
     * Entity for Leads
     * @var App\Service\Spotio\Entity\SpotioLeadEntity
     */
    protected $leadEntity;

    /**
     * Entity for Customer Leads
     * @var App\Service\Spotio\Entity\CustomerLeadEntity
     */
    protected $customerLeadEntity;

    /**
     * Spotio Lead Repository
     * @var App\Repositories\SpotioLeadsRepository;
     */
    protected $repo;

    /**
     * Inject Dependencies
     * @param SpotioLeadsRepository $repo
     * @param SpotioLeadService     $service
     * @param Larasponse            $response
     */
    public function __construct(SpotioLeadService $service, Larasponse $response, SpotioLeadEntity $leadEntity, SpotioLeadRepository $repo, CustomerLeadEntity $customerLeadEntity)
    {
        $this->service    = $service;
        $this->response   = $response;
        $this->leadEntity = $leadEntity;
        $this->customerLeadEntity = $customerLeadEntity;
        $this->repo = $repo;

        parent::__construct();

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
    }

    /**
     * Store a newly created resource in storage.
     * POST /spotio/create_lead
     *
     * @return Response
     */
    public function createLead()
    {
        $inputs = Request::all();
        try {
            $entity = $this->leadEntity->setItems($inputs);
            $lead = $this->service->createLead($entity->getItems());

            return ApiResponse::success([
                'message' => trans('response.success.saved', ['attribute' => 'Lead']),
            ]);
        } catch (Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Add Document in Job stored in DB
     * POST /spotio/add_documents
     * 
     * @return Response
     */
    public function addDocuments()
    {
        $inputs = Request::all();
        try {
            $entity = $this->leadEntity->setItems($inputs);
            $documents = $this->service->addDocuments($entity->getItems());

            return ApiResponse::success([
                'message' => trans('response.success.saved', ['attribute' => 'Documents']),
            ]);
        } catch (Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Update a resource stored in Database
     * POST /spotio/update_lead
     * 
     * @return Response
     */
    public function updateLead()
    {
        $inputs = Request::all();
        try {
            $entity = $this->leadEntity->setItems($inputs);
            $lead = $this->service->updateLead($entity->getItems());

            return ApiResponse::success([
                'message' => trans('response.success.updated', ['attribute' => 'Lead']),
            ]);
        } catch (Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Spotio Leads Listing
     * GET /spotio/index
     * 
     * @return Response
     */
    public function index()
    {
        $input = Request::onlyLegacy('limit', 'lead_id', 'sort_by', 'order');

        $leads = $this->repo->getLeads($input);
        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');
        if (!$limit) {
            return ApiResponse::success($this->response->collection($leads->get(), new SpotioLeadTransformer));
        }
        $leads = $leads->paginate($limit);
        return ApiResponse::success($this->response->paginatedCollection($leads, new SpotioLeadTransformer));
    }

    public function createCustomer()
    {
        $inputs = Request::all();
        try {
            $entity = $this->customerLeadEntity->setItems($inputs);
            $lead = $this->service->createCustomer($entity->getItems());

            return ApiResponse::success([
                'message' => trans('response.success.saved', ['attribute' => 'Customer']),
            ]);
        } catch (Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }
}
