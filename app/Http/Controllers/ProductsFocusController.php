<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\ProductsFocus;
use App\Models\ProductsFocusImage;
use App\Repositories\ProductsFocusRepository;
use FlySystem;
use App\Transformers\ProductsFocusTransformer;
use Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;
use Sorskod\Larasponse\Larasponse;

class ProductsFocusController extends ApiController
{

    protected $repo;
    protected $response;

    public function __construct(ProductsFocusRepository $repo, Larasponse $response)
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
     * GET /productfocus
     *
     * @return Response
     */
    public function index()
    {
        $input = Request::all();
        $products = $this->repo->getFilteredProducts($input);
        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

        if (!$limit) {
            return ApiResponse::success($this->response->collection($products->get(), new ProductsFocusTransformer));
        }
        $products = $products->paginate($limit);
        return ApiResponse::success($this->response->paginatedCollection($products, new ProductsFocusTransformer));
    }

    /**
     * Store a newly created resource in storage.
     * POST /productfocus
     *
     * @return Response
     */
    public function store()
    {
        $input = Request::all();
        $validator = Validator::make($input, ProductsFocus::getRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            $product = $this->repo->saveProduct(
                $input['name'],
                $input['description'],
                isset($input['trades']) ? $input['trades'] : [],
                $input
            );
            if (ine($input, 'images')) {
                $this->saveImages($product->id, $input['images']);
            }

            return ApiResponse::success([
                'message' => Lang::get('response.success.saved', ['attribute' => 'Product']),
                'data' => $this->response->item($product, new ProductsFocusTransformer),
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    /**
     * Display the specified resource.
     * GET /productfocus/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function show($id)
    {
        $product = ProductsFocus::findOrFail($id);
        return ApiResponse::success($this->response->item($product, new ProductsFocusTransformer));
    }

    /**
     * Update the specified resource in storage.
     * PUT /productfocus/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function update($id)
    {
        $product = ProductsFocus::findOrFail($id);
        $input = Request::all();
        $validator = Validator::make($input, ProductsFocus::getRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $product->update($input);
        $product->for_all_trades = isset($input['for_all_trades']) ? (bool)$input['for_all_trades'] : false;
        $product->save();
        if (!(bool)$product->for_all_trades) {
            $product->trades()->detach();
            $product->trades()->attach($input['trades']);
        } else {
            $product->trades()->detach();
        }

        return ApiResponse::success([
            'message' => Lang::get('response.success.updated', ['attribute' => 'Product']),
            'data' => $this->response->item($product, new ProductsFocusTransformer),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /productfocus/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function destroy($id)
    {
        $product = ProductsFocus::findOrFail($id);
        $product->trades()->detach();
        foreach ($product->images as $image) {
            $this->deleteImage($image);
        }
        $product->delete();
        // $product->delete();
        return ApiResponse::success(['message' => Lang::get('response.success.deleted', ['attribute' => 'Product'])]);
    }

    public function upload_image()
    {
        $input = Request::onlyLegacy('id', 'image');
        $validator = Validator::make($input, ProductsFocus::getImageUploadRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        ProductsFocus::findOrFail($input['id']);

        $imageName = $this->uploadImage($input['image']);
        $productsFocusImage = new ProductsFocusImage([
            'products_focus_id' => $input['id'],
            'image' => 'products_focus/' . $imageName,
            'thumb' => 'products_focus/thumb/' . $imageName,
        ]);
        $productsFocusImage->save();

        return ApiResponse::success([
            'message' => Lang::get('response.success.image_uploaded'),
            'data' => [
                'id' => $productsFocusImage->id,
                'image' => FlySystem::publicUrl(config('jp.BASE_PATH') . $productsFocusImage->image),
                'thumb' => FlySystem::publicUrl(config('jp.BASE_PATH') . $productsFocusImage->thumb),
            ]
        ]);
    }

    public function delete_image($imageId)
    {
        $image = ProductsFocusImage::findOrFail($imageId);
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
            $productsFocusImage = new ProductsFocusImage([
                'products_focus_id' => $id,
                'image' => 'products_focus/' . $imageName,
                'thumb' => 'products_focus/thumb/' . $imageName,
            ]);
            $productsFocusImage->save();
        }
    }

    private function uploadImage($image)
    {
        $imageName = rand() . '_' . $image->getClientOriginalName();
        $imagePath = config('jp.BASE_PATH') . 'products_focus/' . $imageName;
        $thumbPath = config('jp.BASE_PATH') . 'products_focus/thumb/' . $imageName;
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
            $imagePath = config('jp.BASE_PATH') . $image->image;
            FlySystem::delete($imagePath);
        }

        if (!empty($image->thumb)) {
            $thumbPath = config('jp.BASE_PATH') . $image->thumb;
            FlySystem::delete($thumbPath);
        }

        //delete from table
        $image->delete();
    }
}
