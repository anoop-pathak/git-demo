<?php

namespace App\Services\Google;

use App\Exceptions\GoogleAccountNotConnectedException;
use App\Exceptions\GoogleDriveFileNoteFound;
use App\Exceptions\UnableToDownLoadException;
use App\Exceptions\UnsupportedGoogleDriveFile;
use App\Services\Contexts\Context;
use App\Traits\HandleGoogleExpireToken;
use Google_Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GoogleDriveService
{

    use HandleGoogleExpireToken;

    protected $client;
    protected $scope;

    const FOLDER_MIME_TYPE = 'application/vnd.google-apps.folder';

    public function __construct(Context $scope)
    {
        $this->client = new Google_Client();
        $this->client->setClientId(config('google.client_id'));
        $this->client->setClientSecret(config('google.client_secret'));
        $this->client->setState('offline');

        // company scope..
        $this->scope = $scope;

        $this->fileFieldsSelection = 'id, name, mimeType, size, thumbnailLink, iconLink, webViewLink, webContentLink';
    }

    /**
     * List Folders and Files
     * @param  array $input | Input for sort and search
     * @return array
     */
    public function getList($input)
    {
        try {
            $service = new \Google_Service_Drive($this->getClient());

            $optParams = $this->makeQuery($input);

            $results = $service->files->listFiles($optParams);

            $data['files'] = $results->getFiles();

            $data['next_page_token'] = $results->getNextPageToken();

            return $data;
        } catch (\Exception $e) {
            if ($this->isTokenExpireException($e)) {
                $this->fireTokenExpireEvent($this->accessToken);
            }

            if (strpos($e->getMessage(), '(400) Invalid Value') !== false) {
                return [
                    'files' => [],
                    'next_page_token' => null
                ];
            }

            throw $e;
        }
    }

    /**
     * Get By Id
     * @param  string $fileId | File Id
     * @return File Object
     */
    public function getById($fileId)
    {
        try {
            $service = new \Google_Service_Drive($this->getClient());

            $optParams['fields'] = $this->fileFieldsSelection;

            return $service->files->get($fileId, $optParams);
        } catch (\Exception $e) {
            if ($e->getCode() == 404) {
                throw new GoogleDriveFileNoteFound(trans('response.error.file_not_exists_in_google_drive'), 404);
            }

            if ($this->isTokenExpireException($e)) {
                $this->fireTokenExpireEvent($this->accessToken);
            }

            throw $e;
        }
    }

    /**
     * Check File is either normal file or Google Doc
     * @param  string $mimeType | Mime type fo file
     * @return boolean
     */
    public function isGoogleDoc($mimeType)
    {
        if (!$mimeType) {
            return false;
        }

        return in_array($mimeType, config('google.google_doc_mime_types'));
    }

    /**
     * Google Mime Type Conversions
     * @param  string $mimeType | Mime type fo file
     * @return string | null
     */
    public function googleMimeConversion($mimeType)
    {
        if (!$mimeType) {
            return null;
        }

        $conversions = config('google.google_doc_conversions');
        if (isset($conversions[$mimeType])) {
            return $conversions[$mimeType];
        }

        return null;
    }

    /**
     * Get File Content
     * @param  object $file | Google File Object
     * @return file
     */
    public function getContent($file)
    {
        try {
            $service = new \Google_Service_Drive($this->getClient());

            $mimeType = $file->mimeType;

            if ($this->isGoogleDoc($mimeType)) {
                $mimeType = $this->googleMimeConversion($mimeType);

                if (!$mimeType) {
                    throw new UnsupportedGoogleDriveFile(trans('response.error.google_drive_unsupported_file'));
                }

                $content = $service->files->export($file->id, $mimeType, ['alt' => 'media']);
            } else {
                $content = $service->files->get($file->id, ['alt' => 'media']);
            }

            return $content;
        } catch (\Exception $e) {
            if ($e->getCode() == 403) {
                Log::warning($e);
                throw new UnableToDownLoadException($e->getMessage());
            }

            if ($e->getCode() == 404) {
                throw new GoogleDriveFileNoteFound(trans('response.error.file_not_exists_in_google_drive'), 404);
            }

            if ($this->isTokenExpireException($e)) {
                $this->fireTokenExpireEvent($this->accessToken);
            }

            throw $e;
        }
    }


    /**************** Private Section ********************/

    /**
     * Get Google Client Object
     * @return object
     */
    private function getClient()
    {
        $currentUser = \Auth::user();

        $googleClient = $currentUser->googleClient()->drive()->first();

        if (!$googleClient || !($googleClient->token)) {
            throw new GoogleAccountNotConnectedException(trans('response.error.google_account_not_connected'));
        }

        $this->accessToken = $googleClient->token;

        $this->client->setAccessToken($googleClient->token);

        return $this->client;
    }

    /**
     * Make Google Query for search
     * @param  array $filters | Array of search input
     * @return array
     */
    private function makeQuery($filters)
    {
        $optParams = [];

        // query default..
        $query = [
            "trashed = false", // exclude deleted..
        ];

        // set default fields
        $optParams['fields'] = "nextPageToken, files({$this->fileFieldsSelection})";

        // set sort order
        $optParams['orderBy'] = 'folder, createdTime desc'; // latest first

        // set limit..
        if (ine($filters, 'limit')) {
            $optParams['pageSize'] = $filters['limit'];
        } else {
            $optParams['pageSize'] = 20;
        }

        // set page..
        if (ine($filters, 'page_token')) {
            $optParams['pageToken'] = $filters['page_token'];
        }

        // type search file/dir default all..
        if (ine($filters, 'type')) {
            $dirMimeType = self::FOLDER_MIME_TYPE;

            switch ($filters['type']) {
                case 'file':
                    $query[] = "mimeType != '{$dirMimeType}'"; // only files..
                    break;

                case 'dir':
                    $query[] = "mimeType = '{$dirMimeType}'"; // only files..
                    break;

                default:
                    // none
                    break;
            }
        }

        // name search..
        if (ine($filters, 'name')) {
            $name = str_replace("'", "\'", $filters['name']);
            $query[] = "name contains '{$name}'";
        }

        if (!ine($filters, 'go_nested')) {
            // parent dir..
            if (ine($filters, 'parent') && ($filters['parent'] == 'sharedWithMe')) {
                $query[] = "sharedWithMe";
            } elseif (ine($filters, 'parent')) {
                $query[] = "'{$filters['parent']}' in parents";
            } else {
                $query[] = "'root' in parents";
            }
        }

        $optParams['q'] = implode(' and ', $query);

        return $optParams;
    }
}
