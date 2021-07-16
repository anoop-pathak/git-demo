<?php

namespace App\Services\Zendesk;

use App\JWTHelper\JWT;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Zendesk\API\Client as ZendeskAPI;

class ZendeskService
{

    protected $client;

    function __construct()
    {

        $subdomain = config('zendesk.subdomain');
        $username = config('zendesk.username');
        $token = config('zendesk.api_key');
        $this->client = new ZendeskAPI($subdomain, $username);
        $this->client->setAuth('token', $token);
    }

    public function authentication(User $user)
    {
        $key = config('zendesk.secret_key');
        $subdomain = config('zendesk.subdomain');
        $now = time();
        $payload = [
            "jti" => md5($now . rand()),
            "iat" => $now,
            "name" => $user->first_name . ' ' . $user->last_name,
            "email" => $user->email,
            "external_id" => $user->id,
        ];

        if (isset($user->company) && !empty($user->company->zendesk_id)) {
            $payload["organization_id"] = $user->company->zendesk_id;
        }

        $jwt = JWT::encode($payload, $key);
        $location = "https://" . $subdomain . ".zendesk.com/access/jwt?jwt=" . $jwt;
        return $location;
    }

    public function addOrganization(Company $company)
    {
        try {
            $response = $this->client->organizations()->create([
                'name' => $company->name,
                'external_id' => $company->id
            ]);
            return $response->organization;
        } catch (\Exception $e) {
            // dd($this->client->getDebug());
            Log::error('Zendesk Error : ' . $e);
        }
    }

    public function addUser(User $user, $organizationId, $role = 'end-user')
    {
        try {
            $response = $this->client->users()->create([
                'name' => $user->first_name . ' ' . $user->last_name." ({$user->id})",
                // 'email' => $user->email,
                'external_id' => $user->id,
                'organization_id' => $organizationId,
                'role' => $role,
            ]);
            return $response->user;
        } catch (\Exception $e) {
            // dd($this->client->getDebug());
            Log::error('Zendesk Error : ' . $e);
        }
    }

    public function updateUser(User $user)
    {
        try {
            $fields = [
                'id' => $user->zendesk_id,
                'name' => $user->first_name . ' ' . $user->last_name." ({$user->id})",
                // 'email' => $user->email,
            ];
            $response = $this->client->users()->update($fields);
            return $response->user;
        } catch (\Exception $e) {
            // dd($this->client->getDebug());
            // 	// Log::error('Zendesk Error : '.$e);
        }
    }

    public function createTicket(User $requester, $subject, $message, $attachments = [], $priority = 'high')
    {
        try {
            $response = $this->client->tickets()->create([
                'subject' => $subject,
                'comment' => [
                    'body' => $message,
                    'uploads' => $attachments
                ],
                'priority' => $priority,
                'requester' => [
                    'name' => $requester->first_name . ' ' . $requester->last_name,
                    'email' => $requester->email
                ],
            ]);
            return $response->ticket;
        } catch (\Exception $e) {
            // dd($this->client->getDebug());
            // Log::error('Zendesk Error : '.$e);
        }
    }

    public function uploadFile($file)
    {

        $file = $this->client->attachments()->upload([
            'file' => $file->getRealPath(),
            'type' => $file->getMimeType(),
            'name' => $file->getClientOriginalName(),
        ]);
        return $file->upload->token;
    }

    public function deleteAttachment($token)
    {
        try {
            $file = $this->client->attachments()->delete(['token' => $token]);
        } catch (\Exception $e) {
            // dd($this->client->getDebug());
        }
    }
}
