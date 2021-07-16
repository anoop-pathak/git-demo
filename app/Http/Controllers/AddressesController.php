<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\ApiResponse;
use App\Repositories\CustomerListingRepository;
use App\Services\Contexts\Context;
use Illuminate\Support\Facades\DB;
use Request;

class AddressesController extends ApiController
{

    /**
     * Compnay Scope
     * @var \App\Services\Contexts\Context
     */
    protected $scope;

    /**
     * Customer Repo
     * @var \App\Repositories\CustomerRepositories
     */
    protected $customerRepo;

    public function __construct(Context $scope, CustomerListingRepository $customerRepo)
    {
        $this->scope = $scope;
        $this->customerRepo = $customerRepo;
        parent::__construct();
    }

    /**
     * Get Cities List
     * Get /cities
     *
     * @return Response
     */
    public function get_cities()
    {

        $citiesWithCustomerCount = Address::whereHas('customer', function ($customer) {
            $customer->own();
        })->whereNotNull('city')
            ->where('addresses.company_id', $this->scope->id())
            ->leftJoin(DB::raw("(select id, address_id from customers where deleted_at IS NULL) as customers"), 'customers.address_id', '=', 'addresses.id')
            ->leftJoin('states', 'states.id', '=', 'addresses.state_id')
            ->selectRaw('addresses.city as city, states.name as state, COUNT(customers.id) as customers')
            ->groupBy('addresses.city')
            ->get();

        return ApiResponse::success(['data' => $citiesWithCustomerCount]);
    }

    /**
     * Get job citites
     * Get /jobs/cities
     * @return Cities
     */
    public function getJobCities()
    {
        $input = Request::all();

        $cities = Address::Join("jobs", function ($query) {
            $query->on('addresses.id', '=', 'jobs.address_id');
            $query->whereNull('jobs.deleted_at');
        })->leftJoin('states', function ($query) {
            $query->on('states.id', '=', 'addresses.state_id');
        })->selectRaw('addresses.city as city, states.name as state')
            ->where('city', '<>', '')
            ->groupBy("addresses.city");

        if (ine($input, 'keyword')) {
            $cities->where('addresses.city', 'Like', $input['keyword'] . '%');
        }

        $cities = $cities->get();

        return ApiResponse::success(['data' => $cities]);
    }

    /**
     * get company's city list
     *
     * GET - /company_city_list
     *
     * @return response
     */
    public function companyCityList()
    {
        $cityList = Address::whereCompanyId($this->scope->id())
            ->whereNotNull('city')
            ->where('city', '<>', '')
            ->groupBy('city')
            ->get();

        return ApiResponse::success(['data' => $cityList->pluck('city')->toArray()]);
    }
}
