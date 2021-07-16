<?php

namespace App\Repositories;

use App\Models\Address;
use App\Models\Customer;
use App\Services\Contexts\Context;
use Illuminate\Support\Facades\DB;

class CustomerListingRepository extends CustomerRepository
{

    /**
     * The base eloquent customer
     * @var Eloquent
     */
    protected $model;
    protected $scope;

    function __construct(Customer $model, Context $scope)
    {
        $this->model = $model;
        $this->scope = $scope;
    }

    public function getFilteredCustomers($filters, $sortable = true)
    {

        $customers = $this->getCustomers($sortable, $filters);

        $this->applyFilters($customers, $filters);
        return $customers;
    }

    public function getFilteredCustomersCount($filters, $sortable = true)
    {

        $customers = $this->getCustomers(false, $filters, false, true);

        $this->applyFilters($customers, $filters);

        return $customers->get()->count();
    }

    public function getCustomers($sortable = true, $params = [], $eagerloading = true, $count = false)
    {

        $customers = $this->make();

        if ($sortable) {
            $customers->sortable();
        }


        //sortable treat
        // if(ine($params, 'name') || ine($params, 'keyword')) {
        // 	$orderBy =  'relevance';
        // }

        if (!ine($params, 'keyword') && !ine($params, 'name') && !ine($params, 'customer_note')) {
            $customers->orderBy('customers.created_at', 'DESC');
        }

        $customers->leftJoin('jobs', function ($join) {
            $join->on('jobs.customer_id', '=', 'customers.id')
                ->whereNull('jobs.deleted_at');
        });

        if ((ine($params, 'lat') && ine($params, 'long')) && Address::isDistanceCalculationPossible()) {
            $lat = $params['lat'];
            $long = $params['long'];
            $customers->leftJoin(DB::raw("(select addresses.*,( 3959 * acos( cos( radians($lat) ) * cos( radians( addresses.lat ) )
					   * cos( radians(addresses.long) - radians($long)) + sin(radians($lat))
					   * sin( radians(addresses.lat)))) as distance from addresses) as addresses"), 'addresses.id', '=', 'customers.address_id');
        } else {
            $customers->leftJoin('addresses', 'addresses.id', '=', 'customers.address_id');
        }


        // calculate distance if required..
        if ($count) {
            $customers->select('customers.id');
        } else {
            $customers->select('customers.*');
        }

        if ((ine($params, 'lat') && ine($params, 'long')) && Address::isDistanceCalculationPossible()) {
            $customers->addSelect(DB::raw('addresses.distance as distance'));
        }

        $customers->groupBy('customers.id');

        if ($eagerloading) {
            $with = $this->includeData($params);
            $customers->with($with);
        }

        return $customers;
    }

    public function getCustomerQeuryBuilder($filters = [], $joins = [])
    {
        $query = $this->make();

        $this->addJoins($query, $joins);
        $this->applyFilters($query, $filters);

        return $query;
    }

    /********** Private function **********/

    private function addJoins($query, $joins)
    {
    }


	private function includeData($filter = array())
	{
		$with = [
			'rep.profile',
			'address',
			'address.state',
			'address.country',
			'phones',
			'todayAppointments',
			'upcomingAppointments',
            'flags',
            'referredByReferral',
			'referredByCustomer',
        ];

		$includes = isset($filter['includes']) ? $filter['includes'] : [];

        if(!is_array($includes) || empty($includes)) {
            return $with;
        }

		if(in_array('deleted_by', $includes)) {
            $with[] = 'deletedBy';
        }

        if(in_array('flags.color', $includes)) {
            $with[] = 'flags.color';
        }

        if(in_array('contacts', $includes)) {
            $with[] = 'contacts';
		}

		if(in_array('meta', $includes)) {
			$with[] = 'customerMeta';
		}

        return $with;
	}
}
