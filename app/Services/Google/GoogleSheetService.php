<?php

namespace App\Services\Google;

use App\Exceptions\GoogleAccountNotConnectedException;
use App\Exceptions\GoogleDriveFileNoteFound;
use App\Models\ApiResponse;
use App\Models\GoogleClient;
use App\Services\Contexts\Context;
use App\Traits\HandleGoogleExpireToken;
use App\Exceptions\GoogleSheetTokenExpiredException;

class GoogleSheetService
{

    use HandleGoogleExpireToken;

    const SPREADSHEET_MIME_TYPE = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    const GOOGLE_SPREADSHEET_MIME_TYPE = 'application/vnd.google-apps.SpreadSheet';

    protected $client;

    public function __construct(Context $scope)
    {
        $this->client = new \Google_Client();
        $this->client->setClientId(config('google.client_id'));
        $this->client->setClientSecret(config('google.client_secret'));
        $this->client->setState('offline');

        // company scope..
        $this->scope = $scope;
    }

    /**
     * Create Empty SpreadSheet
     * @param  String $accessToken | Google AccessToken
     * @param  String $name | Sheet Name
     * @return String $spreadSheetId
     */
    public function createEmptySpreadSheet($name = null)
    {
        try {
            $service = new \Google_Service_Sheets($this->getClient());

            $properties = new \Google_Service_Sheets_SpreadSheetProperties();

            if ($name) {
                $properties->setTitle($name);
            }

            $requestBody = new \Google_Service_Sheets_SpreadSheet();

            $requestBody->setProperties($properties);

            $spreadSheet = $service->spreadsheets->create($requestBody);

            $fileId = $spreadSheet->getSpreadSheetId();

            $this->publicPermissions($fileId);

            return $fileId;
        } catch (\Exception $e) {
            if ($this->isTokenExpireException($e)) {
                $this->fireTokenExpireEvent($this->accessToken);
                throw new GoogleSheetTokenExpiredException(trans('response.error.reconnect_google_sheet_account'));
            }

            throw $e;
        }
    }

    /**
     * Import (Upload) file to create SpreadSheet
     * @param  String $accessToken | Google AccessToken
     * @param  File $file | File
     * @return String $fileID
     */
    public function uploadFile($file, $name = null)
    {
        try {
            if (!$name) {
                $name = $file->getClientOriginalName();
            }

            $content = file_get_contents($file->getRealPath());

            $fileId = $this->createSpreadSheetFromFile($content, $name);

            return $fileId;
        } catch (\Exception $e) {
            if ($this->isTokenExpireException($e)) {
                $this->fireTokenExpireEvent($this->accessToken);

                throw new GoogleSheetTokenExpiredException(trans('response.error.reconnect_google_sheet_account'));
            }

            throw $e;
        }
    }

    public function createFromExistingSheet($spreadSheetId, $name = null)
    {
        try {
            $service = new \Google_Service_Drive($this->getClient());

            $postBody = new \Google_Service_Drive_DriveFile();

            if ($name) {
                $postBody->setName($name);
            }

            $file = $service->files->copy($spreadSheetId, $postBody);

            $fileId = $file->getId();

            $this->publicPermissions($fileId);

            return $fileId;
        } catch (\Exception $e) {
            if ($e->getCode() == 404) {
                throw new GoogleDriveFileNoteFound(trans('response.error.source_file_not_exists_in_google_sheets'), 404);
            }

            if ($this->isTokenExpireException($e)) {
                $this->fireTokenExpireEvent($this->accessToken);
                throw new GoogleSheetTokenExpiredException(trans('response.error.reconnect_google_sheet_account'));
            }

            throw $e;
        }
    }

    public function deleteSpreadSheet($spreadSheetId)
    {
        try {
            $service = new \Google_Service_Drive($this->getClient());

            $service->files->delete($spreadSheetId);

            return true;
        } catch (\Exception $e) {
            if ($e->getCode() == 404) {
                throw new GoogleDriveFileNoteFound(trans('response.error.file_not_exists_in_google_sheets'), 404);
            }

            if ($this->isTokenExpireException($e)) {
                $this->fireTokenExpireEvent($this->accessToken);
            }

            throw $e;
        }
    }

    public function renameSpreadSheet($spreadSheetId, $name)
    {
        try {
            $service = new \Google_Service_Drive($this->getClient());

            $postBody = new \Google_Service_Drive_DriveFile(['name' => $name]);

            $service->files->update($spreadSheetId, $postBody);

            return true;
        } catch (\Exception $e) {
            if ($e->getCode() == 404) {
                throw new GoogleDriveFileNoteFound(trans('response.error.file_not_exists_in_google_sheets'), 404);
            }

            if ($this->isTokenExpireException($e)) {
                $this->fireTokenExpireEvent($this->accessToken);
            }

            throw $e;
        }
    }

    /**
     * Export (download) SpreadSheet as file
     * @param  String $accessToken | Google AccessToken
     * @param  String $spreadSheetId | SpreadSheet Id
     * @param  string $mimetype | File format (mime type)
     * @return File contents
     */
    public function downloadFile($spreadSheetId, $mimeType = null)
    {
        try {
            $content = $this->exportGoogleSheetAsFile($spreadSheetId, $mimeType);

            $response = \response($content, 200);

            if (!$mimeType) {
                $mimeType = self::SPREADSHEET_MIME_TYPE;
            }

            $response->header('Content-Type', $mimeType);

            return $response;
        } catch (GoogleAccountNotConnectedException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            if ($e->getCode() == 404) {
                return ApiResponse::errorGeneral(trans('response.error.file_not_exists_in_google_sheets'));
            }

            if ($this->isTokenExpireException($e)) {
                $this->fireTokenExpireEvent($this->accessToken);
                return ApiResponse::errorGeneral(trans('response.error.reconnect_google_sheet_account'));
            }

            throw $e;
        }
    }

    /**
     * Get SpreadSheet sheets list
     * @param  String $accessToken | Google AccessToken
     * @param  String $spreadSheetId | SpreadSheet Id
     * @return [type]                [description]
     */
    public function getSpreadSheetSheets($spreadSheetId)
    {
        try {
            $sheetService = new \Google_Service_Sheets($this->getClient());

            $spreadSheet = $sheetService->spreadsheets->get($spreadSheetId);

            $sheets = [];

            foreach ($spreadSheet->getSheets() as $key => $sheet) {
                $properties = $sheet->offsetGet('modelData')['properties'];

                $sheets[$key]['id'] = $properties['sheetId'];
                $sheets[$key]['title'] = $properties['title'];
            }

            return $sheets;
        } catch (\Exception $e) {
            if ($this->isTokenExpireException($e)) {
                $this->fireTokenExpireEvent($this->accessToken);
            }

            throw $e;
        }
    }

    // public function listGoogleDriveFiles($accessToken)
    // {
    // 	$this->client->setAccessToken($accessToken);

    // 	$service = new \Google_Service_Drive($this->getClient());
    // 	$optParams = array(
    // 	  'pageSize' => 10,
    // 	  'fields' => 'nextPageToken, files(id, name)'
    // 	);

    // 	return $results = $service->files->listFiles($optParams);

    // 	// if (count($results->getFiles()) == 0) {
    // 	//   print "No files found.\n";
    // 	// } else {
    // 	//   print "Files:\n";
    // 	//   foreach ($results->getFiles() as $file) {
    // 	//     printf("%s (%s)\n", $file->getName(), $file->getId());
    // 	//   }
    // 	// }
    // }

    /**************** Private Section ********************/

    /**
     * Create google sheet by importing excel file
     * @param  string $content | File contents
     * @param  string $name | Name of file
     * @param  string $mimeType | Mime type
     * @return File/Sheet Id
     */
    private function createSpreadSheetFromFile($content, $name = null, $mimeType = null)
    {
        $service = new \Google_Service_Drive($this->getClient());

        $fileMetadata = new \Google_Service_Drive_DriveFile();

        $fileMetadata->setMimeType(self::GOOGLE_SPREADSHEET_MIME_TYPE);

        if ($name) {
            $fileMetadata->setName($name);
        }

        if (!$mimeType) {
            $mimeType = self::SPREADSHEET_MIME_TYPE;
        }

        $file = $service->files->create($fileMetadata, [
            'data' => $content,
            'mimeType' => $mimeType,
            'uploadType' => 'multipart',
            'fields' => 'id'
        ]);

        $fileId = $file->getId();

        $this->publicPermissions($fileId);

        return $fileId;
    }

    /**
     * Manage file/sheet access permission (Make it public)
     * @param  string $fileId | Google drive (sheet) Id
     * @return File/sheet id
     */
    private function publicPermissions($fileId)
    {
        $service = new \Google_Service_Drive($this->getClient());

        $permission = new \Google_Service_Drive_Permission([
            'type' => 'anyone',
            'role' => 'writer',
        ]);

        $service->permissions->create(
            $fileId,
            $permission,
            ['fields' => 'id']
        );

        return $fileId;
    }

    /**
     * Export (Download) google sheet as file
     * @param  string $spreadSheetId | Google Sheet ID
     * @param  string $mimeType | File Mime type (in which format file need to download)
     * @return File content
     */
    private function exportGoogleSheetAsFile($spreadSheetId, $mimeType)
    {
        $service = new \Google_Service_Drive($this->getClient());

        if (!$mimeType) {
            $mimeType = self::SPREADSHEET_MIME_TYPE;
        }

        return $response = $service->files->export(
            $spreadSheetId,
            $mimeType,
            [
                'alt' => 'media',
            ]
        );
    }

    /**
     * Get Google Client Object
     * @return object
     */
    private function getClient()
    {
        if (!$this->scope->has()) {
            throw new \Exception("Something went wrong", 1);
        }

        $companyId = $this->scope->id();

        $googleClient = GoogleClient::whereCompanyId($companyId)->first();

        if (!$googleClient || !($googleClient->token)) {
            throw new GoogleAccountNotConnectedException(trans('response.error.google_account_not_connected'));
        }

        $this->accessToken = $googleClient->token;

        $this->client->setAccessToken($googleClient->token);

        return $this->client;
    }
}
