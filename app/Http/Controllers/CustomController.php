<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Repositories\CustomStepRepository;
use Request;
use Illuminate\Support\Facades\Lang;

class CustomController extends ApiController
{

    /**
     * App\Repositories\CustomStepRepository;
     */
    protected $repo;

    public function __construct(CustomStepRepository $repo)
    {
        $this->repo = $repo;
    }

    public function display()
    {
        dd('hello world');
    }

    public function save()
    {
        $inputs = Request::all();
        $step = json_decode($inputs['step'], true);
        if ($this->repo->save($step)) {
            return ApiResponse::success([
                'message' => Lang::get('response.success.saved', ['attribute' => 'Custom Step'])
            ]);
        }

        return ApiResponse::errorNotFound();
    }
}
