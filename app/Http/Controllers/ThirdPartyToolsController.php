<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\ThirdPartyTool;
use App\Repositories\ThirdPartyToolsRepository;
use FlySystem;
use App\Transformers\ThirdPartyToolsTransformer;
use Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;
use Sorskod\Larasponse\Larasponse;

class ThirdPartyToolsController extends Controller
{

    protected $repo;
    protected $response;

    public function __construct(ThirdPartyToolsRepository $repo, Larasponse $response)
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
     * GET /thirdpartytools
     *
     * @return Response
     */
    public function index()
    {
        $input = Request::all();
        $tools = $this->repo->getFilteredTools($input);
        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

        if (!$limit) {
            return ApiResponse::success($this->response->collection($tools->get(), new ThirdPartyToolsTransformer));
        }
        $tools = $tools->paginate($limit);
        return ApiResponse::success($this->response->paginatedCollection($tools, new ThirdPartyToolsTransformer));
    }

    /**
     * Store a newly created resource in storage.
     * POST /thirdpartytools
     *
     * @return Response
     */
    public function store()
    {
        $input = Request::all();
        $validator = Validator::make($input, ThirdPartyTool::getRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            if (Request::hasFile('image')) {
                $imageName = $this->uploadImage($input['image']);
                $input['image'] = 'third_party_tools/' . $imageName;
                $input['thumb'] = 'third_party_tools/thumb/' . $imageName;
            }
            $tool = $this->repo->saveTool(
                $input['title'],
                $input['description'],
                isset($input['trades']) ? $input['trades'] : [],
                $input
            );

            return ApiResponse::success([
                'message' => Lang::get('response.success.saved', ['attribute' => 'Third party tool']),
                'data' => $this->response->item($tool, new ThirdPartyToolsTransformer),
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    /**
     * Display the specified resource.
     * GET /thirdpartytools/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function show($id)
    {
        $tool = ThirdPartyTool::findOrFail($id);
        return ApiResponse::success($this->response->item($tool, new ThirdPartyToolsTransformer));
    }

    /**
     * Update the specified resource in storage.
     * PUT /thirdpartytools/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function update($id)
    {
        $tool = ThirdPartyTool::findOrFail($id);
        $input = Request::all();
        $validator = Validator::make($input, ThirdPartyTool::getRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            $tool->update($input);
            $tool->for_all_trades = isset($input['for_all_trades']) ? (bool)$input['for_all_trades'] : false;
            $tool->save();
            if (!(bool)$tool->for_all_trades) {
                $tool->trades()->detach();
                $tool->trades()->attach($input['trades']);
            } else {
                $tool->trades()->detach();
            }

            return ApiResponse::success([
                'message' => Lang::get('response.success.updated', ['attribute' => 'Third party tool']),
                'data' => $this->response->item($tool, new ThirdPartyToolsTransformer),
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /thirdpartytools/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function destroy($id)
    {
        $tool = ThirdPartyTool::findOrFail($id);
        $tool->trades()->detach();
        $this->deleteImage($tool);
        if ($tool->delete()) {
            return ApiResponse::success(['message' => Lang::get('response.success.deleted', ['attribute' => 'Third party tool'])]);
        }
        return ApiResponse::errorInternal();
    }

    public function upload_image()
    {
        $input = Request::onlyLegacy('id', 'image');
        $validator = Validator::make($input, ThirdPartyTool::getImageUploadRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $tool = ThirdPartyTool::findOrFail($input['id']);
        $this->deleteImage($tool);
        $imageName = $this->uploadImage($input['image']);
        $tool->image = 'third_party_tools/' . $imageName;
        $tool->thumb = 'third_party_tools/thumb/' . $imageName;
        $tool->save();

        return ApiResponse::success([
            'message' => Lang::get('response.success.image_uploaded'),
            'data' => [
                'image' => FlySystem::publicUrl(config('jp.BASE_PATH') . $tool->image),
                'thumb' => FlySystem::publicUrl(config('jp.BASE_PATH') . $tool->thumb),
            ]
        ]);
    }

    public function delete_image($id)
    {
        $tool = ThirdPartyTool::findOrFail($id);
        try {
            $this->deleteImage($tool);
            $tool->image = null;
            $tool->save();
            return ApiResponse::success(['message' => Lang::get('response.success.image_deleted')]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal();
        }
    }

    /********************* Private function *************************/

    private function uploadImage($image)
    {
        $imageName = rand() . '_' . $image->getClientOriginalName();
        $imagePath = config('jp.BASE_PATH') . 'third_party_tools/' . $imageName;
        $thumbPath = config('jp.BASE_PATH') . 'third_party_tools/thumb/' . $imageName;
        // save image..
        $image = \Image::make($image);
        FlySystem::put($imagePath, $image->encode()->getEncoded());

        //save thumb..
        if ($image->height() > $image->width()) {
            $image->heighten(250, function ($constraint) {
                $constraint->upsize();
            });
        } else {
            $image->widen(250, function ($constraint) {
                $constraint->upsize();
            });
        }
        $thumb = $image->encode();
        FlySystem::put($thumbPath, $thumb->getEncoded());

        return $imageName;
    }

    private function deleteImage(ThirdPartyTool $tool)
    {
        if (!empty($tool->image)) {
            $imagePath = config('jp.BASE_PATH') . $tool->image;
            FlySystem::delete($imagePath);
        }

        if (!empty($tool->thumb)) {
            $thumbPath = config('jp.BASE_PATH') . $tool->thumb;
            FlySystem::delete($thumbPath);
        }
    }
}
