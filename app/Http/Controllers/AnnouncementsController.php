<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\ApiResponse;
use App\Repositories\AnnouncementsRepository;
use App\Transformers\AnnouncementsTransformer;
use Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;

class AnnouncementsController extends Controller
{

    protected $repo;
    protected $response;

    public function __construct(AnnouncementsRepository $repo, Larasponse $response)
    {
        $this->repo = $repo;
        $this->response = $response;
        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     * GET /announcements
     *
     * @return Response
     */
    public function index()
    {
        $input = Request::all();
        $announcements = $this->repo->getFilteredAnnouncements($input);
        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

        if (!$limit) {
            return ApiResponse::success($this->response->collection($announcements->get(), new AnnouncementsTransformer));
        }
        $announcements = $announcements->paginate($limit);
        return ApiResponse::success($this->response->paginatedCollection($announcements, new AnnouncementsTransformer));
    }

    /**
     * Store a newly created resource in storage.
     * POST /announcements
     *
     * @return Response
     */
    public function store()
    {
        $input = Request::all();
        $validator = Validator::make($input, Announcement::getRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            $announcement = $this->repo->createAnnouncement(
                $input['title'],
                $input['description'],
                isset($input['trades']) ? $input['trades'] : [],
                $input
            );

            return ApiResponse::success([
                'message' => Lang::get('response.success.saved', ['attribute' => 'Announcement']),
                'data' => $this->response->item($announcement, new AnnouncementsTransformer),
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    /**
     * Display the specified resource.
     * GET /announcements/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function show($id)
    {
        $announcement = Announcement::findOrFail($id);
        return ApiResponse::success($this->response->item($announcement, new AnnouncementsTransformer));
    }

    /**
     * Update the specified resource in storage.
     * PUT /announcements/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function update($id)
    {
        $announcement = Announcement::findOrFail($id);
        $input = Request::all();
        $validator = Validator::make($input, Announcement::getRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            $announcement->update($input);
            $announcement->for_all_trades = isset($input['for_all_trades']) ? (bool)$input['for_all_trades'] : false;
            $announcement->save();
            if (!(bool)$announcement->for_all_trades) {
                $announcement->trades()->detach();
                $announcement->trades()->attach($input['trades']);
            } else {
                $announcement->trades()->detach();
            }

            return ApiResponse::success([
                'message' => Lang::get('response.success.updated', ['attribute' => 'Announcement']),
                'data' => $this->response->item($announcement, new AnnouncementsTransformer),
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /announcements/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function destroy($id)
    {
        $announcement = Announcement::findOrFail($id);
        $announcement->trades()->detach();
        if ($announcement->delete()) {
            return ApiResponse::success(['message' => Lang::get('response.success.deleted', ['attribute' => 'Announcement'])]);
        }
        return ApiResponse::errorInternal();
    }
}
