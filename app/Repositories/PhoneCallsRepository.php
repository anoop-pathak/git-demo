<?php
namespace App\Repositories;

use App\Services\Contexts\Context;
use App\Models\PhoneCall;
use Illuminate\Support\Facades\Auth;

Class PhoneCallsRepository extends ScopedRepository{

    /**
     * The base eloquent model
     * @var Eloquent
     */
    protected $model;
    protected $scope;

    function __construct(PhoneCall $model,Context $scope){

        $this->scope = $scope;
        $this->model = $model;
    }

    public function getFilteredCalls($filters,  $sortable = true)
    {
        $calls = $this->getCalls($sortable, $filters);
        $this->applyFilters($calls, $filters);

        return $calls;
    }

    public function getById($id, array $with = array())
    {
        $query = $this->make($with);
        return $query->findOrFail($id);
    }

    public function getCalls($sortable = true, $params = array())
    {
        $calls = null;
        if($sortable){
            $calls = $this->make()->Sortable();
        }else{
            $calls = $this->make();
        }

        return $calls;
    }

    /**
     * save phone call Details
     * @return phone call
     */
    public function save($callData, $meta = array())
    {
        if(empty($callData)) return false;

        $companyId = getScopeId();
        $data = [
        	'sid'           => $callData['sid'],
            'company_id'    => $companyId,
            'customer_id'   => ine($meta,'customer_id') ? $meta['customer_id']: null,
            'call_by'       => Auth::id(),
        	'duration'      => $callData['duration'],
        	'status'        => $callData['status'],
            'from_number'   => $callData['from'],
            'to_number'     => $callData['to'],
        ];
    	$callObj = PhoneCall::create($data);

        return $callObj;
    }

    /*************** Private Functions *********************/
    private function applyFilters($calls, $filters)
    {

    }

}