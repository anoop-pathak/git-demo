<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\Flag;
use App\Repositories\CustomerRepository;
use App\Repositories\FlagsRepository;
use App\Repositories\JobRepository;
use App\Transformers\FlagsTransformer;
use Request;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;

class FlagsController extends Controller
{

    /**
     * Flag Repo
     * @var \App\Repositories\FlagsRepositories
     */
    protected $repo;

    public function __construct(FlagsRepository $repo, JobRepository $job, CustomerRepository $customer, Larasponse $response)
    {
        $this->repo = $repo;
        $this->job = $job;
        $this->customer = $customer;
        $this->response = $response;
        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
        parent::__construct();
    }

    /**
     *
     * GET /flags/list
     *
     * @return listing of the flag.
     */
    public function getlist()
    {
        $input = Request::all();
        // limit set to zero default temporarily to solve mobile issue..
        $limit = isset($input['limit']) ? $input['limit'] : 0;
        $flags = $this->repo->getFilteredFlags($input);

        if (!$limit) {
            $flags = $flags->get();

            return ApiResponse::success($this->response->collection($flags, new FlagsTransformer));
        }
        $flags = $flags->paginate($limit);

        return ApiResponse::success($this->response->paginatedCollection($flags, new FlagsTransformer));
    }

    /**
     * Apply Flags
     * PUT flags/apply
     * @return Response
     */
    public function apply()
    {
        $input = Request::onlyLegacy('id', 'flag_for', 'flag_id', 'status');
        $validator = Validator::make($input, Flag::getApplyRule());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $repo = $this->{$input['flag_for']}->getById($input['id']);
        try {
            $repo->flags()->detach(['flag_id' => $input['flag_id']]);
            if ($input['status']) {
                $repo->flags()->attach(['flag_id' => $input['flag_id']]);
            }

            return ApiResponse::success([
                'message' => trans('response.success.applied', ['attribute' => 'Flag']),
                'flags' => $this->response->collection($repo->flags, new FlagsTransformer)['data']
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * PUT flags/apply_multiple_flag (Apply Multiple flag for customer or job)
     * @return [json] [message + attach flag]
     */
    public function applyMultipleFlag()
    {
        $input = Request::onlyLegacy('flag_for', 'flag_ids', 'id');
        $validator = Validator::make($input, Flag::getApplyMutipleFlagRule());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $repo = $this->{$input['flag_for']}->getById($input['id']);
        try {
            $repo->flags()->detach();
            if (ine($input, 'flag_ids')) {
                $repo->flags()->attach($input['flag_ids']);
            }

            return ApiResponse::success([
                'message' => trans('response.success.applied', ['attribute' => 'Flags']),
                'flags' => $this->response->collection($repo->flags, new FlagsTransformer)['data']
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Flag Save
     * POST /flags
     * @return [type] [description]
     */
    public function store()
    {
        $input = Request::onlyLegacy('title', 'flag_for', 'color');
        $validator = Validator::make($input, Flag::getSaveFlagRule());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        if ($this->repo->isFlagExist($input['title'], $input['flag_for'])) {
            return ApiResponse::errorGeneral(trans('response.error.already_exist', ['attribute' => 'Flag']));
        }

        try {
            $flag = $this->repo->save($input['title'], $input['flag_for'], $input);

            return ApiResponse::success([
                'message' => trans('response.success.saved', ['attribute' => 'Flag']),
                'flag' => $this->response->item($flag, new FlagsTransformer)
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Flag Updated
     * PUT /flags/{flag_id}
     * @param  int $id flag id
     * @return Response
     */
    public function update($id)
    {
        $flag = $this->repo->getById($id);

        $input = Request::onlyLegacy('title', 'color');
        $validator = Validator::make($input, ['title' => 'required', 'color' => 'color_code']);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        if ($this->repo->isFlagExist($input['title'], $flag->for, $flag->id)) {
            return ApiResponse::errorGeneral(
                trans('response.error.already_exist', ['attribute' => 'Flag'])
            );
        }

        $data = ['title' => $input['title']];

        try {
            $flag->update($data);

            if(isset($input['color'])) {
				$flag = $this->repo->saveColor($flag, $input['color']);
			}

            return ApiResponse::success([
                'message' => trans('response.success.updated', ['attribute' => 'Flag']),
                'flag' => $this->response->item($flag, new FlagsTransformer)
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Flag Delete
     * DELETE /flags/{flag id}
     * @param  Int $id Flag Id
     * @return Response
     */
    public function destroy($id)
    {
        $flag = $this->repo->getSystemFlagById($id);

        if ($flag->jobs->count() || $flag->customers->count() || $flag->isReservedFlag()) {

            if($customerCount = $flag->customers->count()) {
                return ApiResponse::errorGeneral(
                    "This flag has assigned to {$customerCount} customer(s), you can't delete this flag."
                );
            }

            if($jobCount = $flag->jobs->count()) {
                return ApiResponse::errorGeneral(
                    "This flag has assigned to {$jobCount} job(s), you can't delete this flag."
                );
            }

            return ApiResponse::errorGeneral(
                trans('response.error.not_deleted', ['attribute' => 'Flag'])
            );
        }

        try {
            if ($flag->isSystemFlag()) {
                $companyId = $this->repo->scope->id();
                $flag->companyDeletedFlags()->sync((array)$companyId, false);
            } else {
                $flag->delete();
            }

            return ApiResponse::success([
                'message' => trans('response.success.deleted', ['attribute' => 'Flag']),
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
	 * save flag color
	 * PUT - /flags/{id}/save_color
	 *
	 * @param  Integer | $id | Flag id
	 * @return response
	 */
	public function saveColor($id)
	{
		$input = Request::all();
		$flag = Flag::findOrFail($id);

 		$validator = Validator::make($input, [
			'color' => 'color_code',
		]);

 		if($validator->fails()) {

 			return ApiResponse::validation($validator);
		}

 		try {
			$flag = $this->repo->saveColor($flag, $input['color']);

 			return ApiResponse::success([
				'message' => trans('response.success.saved', ['attribute' => 'Flag color']),
			]);
		} catch (\Exception $e) {
			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}
}
