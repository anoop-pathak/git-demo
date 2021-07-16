<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\Notification;
use App\Repositories\NotificationsRepository;
use App\Transformers\NotificationsTransformer;
use Illuminate\Support\Facades\Auth;
use Request;
use Illuminate\Support\Facades\Lang;
use Sorskod\Larasponse\Larasponse;

class NotificationsController extends ApiController
{

    protected $repo;
    protected $response;

    public function __construct(NotificationsRepository $repo, Larasponse $response)
    {
        $this->repo = $repo;
        $this->response = $response;
        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
        parent::__construct();
    }

    public function get_unread_notifications()
    {
        $input = Request::all();
        $notifications = $this->repo->getUnreadNotifications(\Auth::user());
        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');
        if (!$limit) {
            return ApiResponse::success($this->response->collection($notifications->get(), new NotificationsTransformer));
        }
        $notifications = $notifications->paginate($limit);
        return ApiResponse::success($this->response->paginatedCollection($notifications, new NotificationsTransformer));
    }

    public function unread_notifications_count()
    {
        $counts = $this->repo->unreadNotificationCount(\Auth::user());
        return ApiResponse::success([
            'counts' => $counts
        ]);
    }

    public function mark_as_read($notificationId)
    {
        $user = \Auth::user();
        if ($notificationId == 'all') {
            $this->repo->markAsRead($user);
        } else {
            $notification = Notification::findOrFail($notificationId);
            $this->repo->markAsRead($user, $notificationId);
        }
        return ApiResponse::success([
            'message' => Lang::get('response.success.mark_read'),
        ]);
    }
}
