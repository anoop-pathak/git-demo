<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\EmailLabel;
use App\Repositories\EmailLabelRepository;
use App\Services\Contexts\Context;
use App\Transformers\EmailLabelTransformer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Request;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;

class EmailLabelsController extends ApiController
{
    protected $response;
    protected $repo;
    protected $scope;

    function __construct(EmailLabelRepository $repo, Larasponse $response, Context $scope)
    {
        $this->repo = $repo;
        $this->response = $response;
        $this->scope = $scope;
        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }

        parent::__construct();
    }

    /**
     * Display a listing of the Email Labels.
     *
     * @return Response
     */
    public function index()
    {
        $input = Request::all();

        $companyId = getScopeId();
        $userId = \Auth::id();

        $labels = $this->repo->getFilteredLabels($input);
        $labels->select('email_labels.id', 'name', 'email_labels.created_at', 'email_labels.updated_at');
        if (ine($input, 'with_unread_count')) {
            $labels->leftJoin(DB::raw("(SELECT * FROM emails WHERE company_id={$companyId} AND is_read = 0 group by conversation_id) as emails"), function ($join)
 use ($userId) {
                $join->on('emails.label_id', '=', 'email_labels.id')
                    ->where('emails.created_by', '=', $userId)
                    ->whereNull('emails.deleted_at');
            })->groupBy('email_labels.id');
            $labels->addSelect(DB::raw('Count(emails.id) as unread_count'));
        }

        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

        if (!$limit) {
            $labels = $labels->get();
            return ApiResponse::success($this->response->collection($labels, new EmailLabelTransformer));
        }
        $labels = $labels->paginate($limit);
        return ApiResponse::success($this->response->paginatedCollection($labels, new EmailLabelTransformer));
    }

    /**
     * Save Email Labels
     * POST/email/labels
     *
     * @return Response
     */
    public function store()
    {
        $input = Request::onlyLegacy('name');

        $validator = Validator::make($input, EmailLabel::getRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            $exists = EmailLabel::where('name', '=', $input['name'])
                ->where('company_id', '=', $this->scope->id())
                ->exists();

            if ($exists) {
                return ApiResponse::errorGeneral('Label already exists');
            }

            $data = $this->repo->saveLabels($input);

            return ApiResponse::success([
                'message' => trans('response.success.saved', ['attribute' => 'Email Label']),
                'email_label' => $this->response->item($data, new EmailLabelTransformer)
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }


    /**
     * Display the specified Email Label.
     * GET/email/labels/{id}
     *
     * @return Response
     */
    public function show($id)
    {
        $label = $this->repo->getById($id);

        return ApiResponse::success([
            'data' => $this->response->item($label, new EmailLabelTransformer)
        ]);
    }

    /**
     * Update Email Labels
     * PUT/email/labels/{id}
     *
     * @return Response
     */
    public function update($id)
    {
        $label = $this->repo->getById($id);

        $input = Request::onlyLegacy('name');

        $validator = Validator::make($input, EmailLabel::getRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            $existingLabel = EmailLabel::where('name', '=', $input['name'])
                ->where('id', '<>', $id)
                ->where('company_id', '=', $this->scope->id())->first();

            if ($existingLabel) {
                return ApiResponse::errorGeneral('Label already exists');
            }

            $label->name = $input['name'];
            $label->update();

            return ApiResponse::success([
                'message' => trans('response.success.updated', ['attribute' => 'Email Label']),
                'email_label' => $this->response->item($label, new EmailLabelTransformer)
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }


    /**
     * Delete the Email Label.
     * DELETE/email/labels/{id}
     *
     * @return Response
     */
    public function destroy($id)
    {
        $label = $this->repo->getById($id);

        try {
            if ($label->emails()->count()) {
                return ApiResponse::errorGeneral(
                    trans("You can't delete this Label. Moved out all emails first.")
                );
            }

            $label->delete();

            return ApiResponse::success([
                'message' => trans('response.success.deleted', ['attribute' => 'Email Label'])
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }
}
