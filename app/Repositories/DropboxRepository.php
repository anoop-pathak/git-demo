<?php

namespace App\Repositories;

use App\Exceptions\DropboxAccountNotConnectedException;
use App\Models\DropboxClient;
use App\Services\Contexts\Context;

class DropboxRepository extends ScopedRepository
{

    /**
     * The base eloquent ActivityLog
     * @var Eloquent
     */
    protected $scope;
    protected $model;

    function __construct(Context $scope, DropboxClient $model)
    {
        $this->scope = $scope;
        $this->model = $model;
    }

    public function save($companyId, $userId, $token, $uid, $accountId, $userEmail)
    {
        try {
            $dropbox = DropboxClient::firstOrNew(['company_id' => $companyId, 'user_id' => $userId]);
            $dropbox->token = $token;
            $dropbox->uid = $uid;
            $dropbox->account_id = $accountId;
            $dropbox->user_name = $userEmail;

            $dropbox->save();

            return $dropbox;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getToken($userId)
    {
        $dropbox = $this->make()
            ->select('token')
            ->whereCompanyId($this->scope->id())
            ->where('user_id', $userId)
            ->first();

        if (empty($dropbox->token)) {
            throw new DropboxAccountNotConnectedException(trans('response.error.dropbox_account_not_connected'));
        }

        return $dropbox->token;
    }

    public function token($userId)
    {
        return $this->getToken($userId);
    }

    /************************ PRIVATE METHODS *******************/
}
