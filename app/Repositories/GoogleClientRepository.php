<?php

namespace App\Repositories;

use App\Models\GoogleClient;

class GoogleClientRepository extends AbstractRepository
{

    /**
     * The base eloquent model
     * @var Eloquent
     */
    protected $model;

    public function __construct(GoogleClient $model)
    {
        $this->model = $model;
    }

    /**
     * @param $email String | google email id
     * @param $accessToken Json-String | offline auth access token
     * @param calenderId String | Calender key or Id
     * @param $channelData Array | Array of channel data with fields channel id, expiration date, resource id
     * @param $syncToken String | Sync token of calender
     * @return GoogleClient object
     */
    public function saveClientData($accessToken, $email, $stateData)
    {
        $stateData = (array)$stateData;

        $data = [
            'email' => $email,
            'token' => $accessToken,
        ];

        $userId = isset($stateData['user_id']) ? $stateData['user_id'] : null;
        $companyId = isset($stateData['company_id']) ? $stateData['company_id'] : null;

        if ($userId) {
            $googleClient = $this->findByUserId($userId);
            $data['user_id'] = $userId;
            $data['scope_calendar_and_tasks'] = ine($stateData, 'scope_calendar_and_tasks');
            $data['scope_drive'] = ine($stateData, 'scope_drive');
            $data['scope_gmail'] = ine($stateData, 'scope_gmail');
        }

        if ($companyId) {
            $googleClient = $this->model->whereCompanyId($companyId)->first();
            $data['company_id'] = $companyId;
            $data['scope_google_sheet'] = true;
        }

        if (!$googleClient) {
            $googleClient = $this->model->create($data);
        } else {
            $googleClient->update($data);
        }

        return $googleClient;
    }

    /**
     * @param $id Integer | Google Client Model Id
     * @param calenderId String | Calender key or Id
     * @param $syncToken String | Calender sync token
     * @param $channelId Integer | Google push notification channel id
     * @param $channelExpireDate date string | Google channel expiration date
     * @param $resourceId date Integer | Calender resource id
     * @return GoogleClient object
     */
    public function saveCalenderDetails($userId, $calenderId, $syncToken = null, $channelId = null, $channelExpireTime = null, $resourceId = null)
    {
        $googleClient = $this->findByUserId($userId);
        $googleClient->calender_id = $calenderId;
        $googleClient->next_sync_token = $syncToken;
        $googleClient->channel_id = $channelId;
        $googleClient->channel_expire_time = $channelExpireTime;
        $googleClient->resource_id = $resourceId;
        $googleClient->save();
        return $googleClient;
    }

    /**
     *Find the Google Client by user id
     * @param $userId Integer | Id associate to user.
     * @return GoogleClient Modle object | Null if mot found
     */
    public function findByUserId($userId)
    {
        return $this->model->where('user_id', '=', $userId)->first();
    }

    /**
     *Find the Google Client by company id
     * @param $companyId Integer | Id associate to user.
     * @return GoogleClient Modle object | Null if mot found
     */
    public function findByCompanyId($companyId)
    {
        return $this->model->where('company_id', '=', $companyId)->first();
    }

    /**
     *Find the Google Client by channel id
     * @param $channelId String | Calender resource notification channel id
     * @return GoogleClient Modle object | Null if mot found
     */
    public function findByChannelId($channelId)
    {
        return $this->model->where('channel_id', '=', $channelId)->first();
    }
}
