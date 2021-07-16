<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\Customer;
use App\Services\Contexts\Context;
use App\Transformers\Optimized\CustomersTransformer;
use Request;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;

class CustomerSearchController extends ApiController
{

    public function __construct(Larasponse $response, Context $scope)
    {
        $this->response = $response;
        $this->scope = $scope;
        parent::__construct();

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
    }

    /**
     * Customer search with  keyword
     * Get /customers/keyword_search
     * @return RESPONSE
     */
    public function keywordSearch()
    {
        $input = Request::all();
        $validator = Validator::make($input, ['keyword' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $keyword = ine($input, 'keyword') ? $input['keyword'] : '';
        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');
        $customers = Customer::where('customers.company_id', config('company_scope_id'))
            ->leftJoin('addresses', function ($join) {
                $join->on('customers.address_id', '=', 'addresses.id');
            })
            ->leftJoin('customer_contacts', function ($join) {
                $join->on('customers.id', '=', 'customer_contacts.customer_id');
            })
            ->with('phones', 'address', 'jobs')
            ->keywordSearch($keyword, $this->scope->id())
            ->select('customers.*')
            ->groupBy('customers.id')
            ->paginate($limit);

        $transformer = (new CustomersTransformer)->setDefaultIncludes([
            'count',
            'address',
            'phones'
        ]);

        return ApiResponse::success(
            $this->response->paginatedCollection($customers, $transformer)
        );
    }
}
