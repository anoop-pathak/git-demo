<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\Company;
use App\Models\TradeNews;
use App\Models\TradeNewsFeed;
use App\Models\TradeNewsUrl;
use App\Repositories\TradeNewsRepository;
use App\Services\Contexts\Context;
use FlySystem;
use App\Transformers\TradeNewsTransformer;
use Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;
use Sorskod\Larasponse\Larasponse;

class TradeNewsController extends Controller
{

    protected $repo;
    protected $response;

    public function __construct(TradeNewsRepository $repo, Larasponse $response, Context $scope)
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
     * Display a listing of the resource.
     * GET /tradenews
     *
     * @return Response
     */
    public function index()
    {
        $input = Request::all();
        $news = $this->repo->getFilteredNews($input);
        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

        if (!$limit) {
            return ApiResponse::success($this->response->collection($news->get(), new TradeNewsTransformer));
        }
        $news = $news->paginate($limit);
        return ApiResponse::success($this->response->paginatedCollection($news, new TradeNewsTransformer));
    }

    /**
     * Store a newly created resource in storage.
     * POST /tradenews
     *
     * @return Response
     */
    public function store()
    {
        $input = Request::all();
        $validator = Validator::make($input, TradeNews::getCreateRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            if (Request::hasFile('image')) {
                $imageName = $this->uploadImage($input['image']);
                $input['image'] = 'trade_news/' . $imageName;
                $input['thumb'] = 'trade_news/thumb/' . $imageName;
            }
            $news = $this->repo->saveTradeNews(
                $input['title'],
                $input['trade_id'],
                $input
            );
            $this->saveUrls($news->id, $input['urls']);
            return ApiResponse::success([
                'message' => Lang::get('response.success.saved', ['attribute' => 'Trade news']),
                'data' => $this->response->item($news, new TradeNewsTransformer),
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
        $news = TradeNews::findOrFail($id);
        return ApiResponse::success($this->response->item($news, new TradeNewsTransformer));
    }

    /**
     * Update the specified resource in storage.
     * PUT /tradenews/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function update($id)
    {
        $news = TradeNews::findOrFail($id);
        $input = Request::all();
        $validator = Validator::make($input, TradeNews::getUpdateRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            $news->update($input);
            $news->save();
            return ApiResponse::success([
                'message' => Lang::get('response.success.updated', ['attribute' => 'Trade news']),
                'data' => $this->response->item($news, new TradeNewsTransformer),
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /tradenews/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function destroy($id)
    {
        $news = TradeNews::findOrFail($id);
        $this->deleteImage($news);
        $news->urls()->delete();
        if ($news->delete()) {
            return ApiResponse::success(['message' => Lang::get('response.success.deleted', ['attribute' => 'Trade news'])]);
        }
        return ApiResponse::errorInternal();
    }

    public function upload_image()
    {
        $input = Request::onlyLegacy('id', 'image');
        $validator = Validator::make($input, TradeNews::getImageUploadRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $tradeNews = TradeNews::findOrFail($input['id']);
        $this->deleteImage($tradeNews);
        $imageName = $this->uploadImage($input['image']);
        $tradeNews->image = 'trade_news/' . $imageName;
        $tradeNews->thumb = 'trade_news/thumb/' . $imageName;
        $tradeNews->save();

        return ApiResponse::success([
            'message' => Lang::get('response.success.image_uploaded'),
            'data' => [
                'image' => FlySystem::publicUrl(config('jp.BASE_PATH') . $tradeNews->image),
                'thumb' => FlySystem::publicUrl(config('jp.BASE_PATH') . $tradeNews->thumb),
            ]
        ]);
    }

    public function delete_image($id)
    {
        $tradeNews = TradeNews::findOrFail($id);
        try {
            $this->deleteImage($tradeNews);
            $tradeNews->image = null;
            $tradeNews->save();
            return ApiResponse::success(['message' => Lang::get('response.success.image_deleted')]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal();
        }
    }

    public function add_url($tradeNewsId)
    {
        //find trade news
        $tradeNews = TradeNews::find($tradeNewsId);
        // if trade news id is invalde..
        if (!$tradeNews) {
            return ApiResponse::errorNotFound(Lang::get('response.error.invalid', ['attribute' => 'trade news id']));
        }
        $input = Request::onlyLegacy('url');
        $validator = Validator::make($input, ['url' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            $url = $this->repo->saveUrl($tradeNewsId, $input['url']);
            return ApiResponse::success([
                'message' => Lang::get('response.success.saved', ['attribute' => 'Url']),
                'data' => [
                    'id' => $url->id,
                    'url' => $url->url
                ]
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal();
        }
    }

    public function delete_url($tradeNewsId, $urlId)
    {

        //find trade news
        $tradeNews = TradeNews::find($tradeNewsId);
        // if trade news id is invalde..
        if (!$tradeNews) {
            return ApiResponse::errorNotFound(Lang::get('response.error.invalid', ['attribute' => 'trade news id']));
        }
        // find requsted url..
        $url = $tradeNews->urls()->find($urlId);
        // if url id is invalde..
        if (!$url) {
            return ApiResponse::errorNotFound('Invalid url id');
        }

        if ($url->delete()) {
            return ApiResponse::success([
                'message' => Lang::get('response.success.deleted', ['attribute' => 'Url'])
            ]);
        }
        return ApiResponse::errorInternal();
    }

    public function activate_url($tradeNewsId, $urlId)
    {

        //find trade news
        $tradeNews = TradeNews::find($tradeNewsId);
        // if trade news id is invalde..
        if (!$tradeNews) {
            return ApiResponse::errorNotFound(Lang::get('response.error.invalid', ['attribute' => 'trade news id']));
        }
        // find requsted url..
        $url = $tradeNews->urls()->find($urlId);
        // if url id is invalde..
        if (!$url) {
            return ApiResponse::errorNotFound('Invalid url id');
        }

        try {
            // inactivate all urls..
            $tradeNews->urls()->update(['active' => false]);
            // activate the requested url..
            $url->update(['active' => true]);
            return ApiResponse::success([
                'message' => Lang::get('response.success.activated', ['attribute' => 'Url'])
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal();
        }
    }


    public function feed()
    {
        $input = Request::all();
        $feed = $this->getFeed($input);

        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');
        $feed = $feed->paginate($limit);
        return ApiResponse::success($this->response->paginatedCollection($feed, function ($feed) {
            return [
                'feed' => $feed->feed,
                'trade' => $feed->trade,
            ];
        }));
    }

    /********************* Private function *************************/

    private function uploadImage($image)
    {
        $imageName = rand() . '_' . $image->getClientOriginalName();
        $imagePath = config('jp.BASE_PATH') . 'trade_news/' . $imageName;
        $thumbPath = config('jp.BASE_PATH') . 'trade_news/thumb/' . $imageName;
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

    private function deleteImage(TradeNews $tradeNews)
    {
        if (!empty($tradeNews->image)) {
            $imagePath = config('jp.BASE_PATH') . $tradeNews->image;
            FlySystem::delete($imagePath);
        }

        if (!empty($tradeNews->image)) {
            $thumbPath = config('jp.BASE_PATH') . $tradeNews->thumb;
            FlySystem::delete($thumbPath);
        }
    }

    private function saveUrls($newsId, $urls = [])
    {

        foreach ($urls as $url) {
            $this->repo->saveUrl($newsId, $url);
        }
        return true;
    }

    private function getFeed($filters = [])
    {

        if (ine($filters, 'trades')) {
            $tradeIds = $filters['trades'];
        } elseif ($this->scope->has()) {
            $companyId = $this->scope->id();
            $tradeIds = Company::find($companyId)->trades()->pluck('trade_id')->toArray();
        } else {
            $tradeIds = [];
        }

        $urls = TradeNewsUrl::trades($tradeIds)->active()->pluck('url')->toArray();
        $feed = TradeNewsFeed::whereIn('url', $urls)->orderBy('order', 'asc');
        return $feed;
    }
}
