<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\IncompleteSignup;
use App\Transformers\IncompleteSignupsTransformer;
use Request;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;

class IncompleteSignupsController extends Controller
{

    protected $response;

    function __construct(Larasponse $response)
    {
        $this->response = $response;
    }

    /**
     * Display a listing of the resource.
     * GET /incomplete_signups
     *
     * @return Response
     */
    public function index()
    {
        $input = Request::all();

        $incompleteSignups = IncompleteSignup::orderBy('id', 'DESC');

        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

        if (!$limit) {
            $incompleteSignups = $incompleteSignups->get();

            return ApiResponse::success($this->response->collection(
                $incompleteSignups,
                new IncompleteSignupsTransformer
            ));
        }
        $incompleteSignups = $incompleteSignups->paginate($limit);

        return ApiResponse::success($this->response->paginatedCollection($incompleteSignups, new IncompleteSignupsTransformer));
    }

    /**
     * Store a newly created resource in storage.
     * POST /incomplete_signups
     *
     * @return Response
     */
    public function store()
    {
        $input = Request::onlyLegacy('token', 'first_name', 'last_name', 'email', 'phone');

        $validator = Validator::make($input, IncompleteSignup::getRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $incompleteSignup = IncompleteSignup::firstOrNew(['token' => $input['token']]);
        $incompleteSignup->first_name = $input['first_name'];
        $incompleteSignup->last_name = $input['last_name'];
        $incompleteSignup->email = $input['email'];
        $incompleteSignup->phone = $input['phone'];

        if ($incompleteSignup->save()) {
            return ApiResponse::success(['message' => 'Signup data saved temporarily.']);
        }

        return ApiResponse::errorInternal();
    }

    /**
     * Display the specified resource.
     * GET /incomplete_signups/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function show($id)
    {
        $incompleteSignup = IncompleteSignup::findOrFail($id);

        return ApiResponse::success(['data' => $incompleteSignup]);
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /incomplete_signups/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function destroy($id)
    {
        $incompleteSignup = IncompleteSignup::findOrFail($id);

        $incompleteSignup->delete();

        return ApiResponse::success(['message' => 'Record deleted successfully.']);
    }
}
