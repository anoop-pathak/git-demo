<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\Classified;
use App\Models\ClassifiedImage;
use App\Repositories\ClassifiedRepository;
use FlySystem;
use App\Transformers\ClassifiedTransformer;
use Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;
use Sorskod\Larasponse\Larasponse;
use App\Services\Contexts\Context;


class ClassifiedsController extends ApiController
{

    protected $repo;
    protected $response;
    protected $scope;

    public function __construct(ClassifiedRepository $repo, Larasponse $response, Context $scope)
    {
        $this->repo     = $repo;
        $this->response = $response;
        $this->scope    = $scope;
        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     * GET /classifieds
     *
     * @return Response
     */
    public function index()
    {
        $input = Request::all();
        $classifieds = $this->repo->getFilteredClassifieds($input);
        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

        if (!$limit) {
            return ApiResponse::success($this->response->collection($classifieds->get(), new ClassifiedTransformer));
        }
        $classifieds = $classifieds->paginate($limit);
        return ApiResponse::success($this->response->paginatedCollection($classifieds, new ClassifiedTransformer));
    }

    /**
     * Store a newly created resource in storage.
     * POST /classifieds
     *
     * @return Response
     */
    public function store()
    {
        $input = Request::all();
        $validator = Validator::make($input, Classified::getRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            $classified = $this->repo->saveClassified(
                $input['name'],
                $input['description'],
                isset($input['trades']) ? $input['trades'] : [],
                $input
            );
            if (ine($input, 'images')) {
                $this->saveImages($classified->id, $input['images']);
            }

            return ApiResponse::success([
                'message' => Lang::get('response.success.saved', ['attribute' => 'Classified']),
                'data' => $this->response->item($classified, new ClassifiedTransformer),
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    /**
     * Display the specified resource.
     * GET /classifieds/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function show($id)
    {
        $classified = Classified::findOrFail($id);
        return ApiResponse::success($this->response->item($classified, new ClassifiedTransformer));
    }

    /**
     * Update the specified resource in storage.
     * PUT /classifieds/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function update($id)
    {
        $companyId = $this->scope->has() ? $this->scope->id() : null;
        $classified = Classified::whereCompanyId($companyId)->findOrFail($id);
        $input = Request::all();
        $validator = Validator::make($input, Classified::getRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $classified->update($input);
        $classified->for_all_trades = isset($input['for_all_trades']) ? (bool)$input['for_all_trades'] : false;
        $classified->save();
        if (!(bool)$classified->for_all_trades) {
            $classified->trades()->detach();
            $classified->trades()->attach($input['trades']);
        } else {
            $classified->trades()->detach();
        }

        return ApiResponse::success([
            'message' => Lang::get('response.success.updated', ['attribute' => 'Classified']),
            'data' => $this->response->item($classified, new ClassifiedTransformer),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /classifieds/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function destroy($id)
    {
        $companyId = $this->scope->has() ? $this->scope->id() : null;
        $classified = Classified::whereCompanyId($companyId)->findOrFail($id);
        $classified->trades()->detach();
        foreach ($classified->images as $image) {
            $this->deleteImage($image);
        }
        $classified->delete();
        // $classified->delete();
        return ApiResponse::success(['message' => Lang::get('response.success.deleted', ['attribute' => 'Classified'])]);
    }

    public function upload_image()
    {
        $input = Request::onlyLegacy('id', 'image');
        $validator = Validator::make($input, Classified::getImageUploadRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $companyId = null;
        if(\Auth::user()->isAuthority() && $this->scope->has()) {
            $companyId = $this->scope->id();
        }

        $imageName = $this->uploadImage($input['image']);
        $classifiedImage = new ClassifiedImage([
            'classified_id' => $input['id'],
            'image' => ClassifiedImage::getBasePath().$imageName,
            'thumb' =>  ClassifiedImage::getThumbBasePath().$imageName,
            'file_with_new_path' => true
        ]);
        $classifiedImage->save();

        return ApiResponse::success([
            'message' => trans('response.success.image_uploaded'),
            'data' => [
                'id' => $classifiedImage->id,
                'image' => FlySystem::publicUrl($classifiedImage->getFilePathWithoutUrl()),
                'thumb' => FlySystem::publicUrl($classifiedImage->getThumbPathWithoutUrl()),
            ]
        ]);
    }

    public function delete_image($imageId)
    {
        $companyId = null;
        if(\Auth::user()->isAuthority() && $this->scope->has()) {
            $companyId = $this->scope->id();
        }
        $image = ClassifiedImage::findOrFail($imageId);
        Classified::whereCompanyId($companyId)->findOrFail($image->classified_id);
        try {
            $this->deleteImage($image);
            return ApiResponse::success(['message' => Lang::get('response.success.deleted', ['attribute' => 'Image'])]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal();
        }
    }

    /********************* Private function *************************/

    private function saveImages($id, $images)
    {
        foreach ($images as $image) {
            $imageName = $this->uploadImage($image);
            $classifiedImage = new ClassifiedImage([
                'classified_id' => $id,
                'image' => ClassifiedImage::getBasePath().$imageName,
                'thumb' => ClassifiedImage::getThumbBasePath().$imageName,
                'file_with_new_path' => true,
            ]);

            $classifiedImage->save();
        }
    }

    private function uploadImage($image)
    {
        $imageName = rand() . '_' . $image->getClientOriginalName();
        $imagePath = ClassifiedImage::getBasePath().$imageName;
        $thumbPath = ClassifiedImage::getThumbBasePath().$imageName;

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

    private function deleteImage($image)
    {
        if (!empty($image->image)) {
            FlySystem::delete($image->getFilePathWithoutUrl());
        }

        if (!empty($image->thumb)) {
            FlySystem::delete($image->getThumbPathWithoutUrl());
        }

        //delete from table
        $image->delete();
    }
}
