<?php

namespace App\Services\DropBox;

use App\Exceptions\DropboxException;
use App\Exceptions\DropboxFileSizeExceededException;
use App\Services\EstimationService;
use App\Services\ProposalService;
use App\Services\Resources\ResourceServices;
use App\Transformers\EstimationsTransformer;
use App\Transformers\ProposalsTransformer;
use App\Transformers\ResourcesTransformer;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

/**
 * @todo Lot of commented out code in class; Verify and remove.
 * @todo Refactor this code; This code is being mulitple times; you could make separate function or something for this.
 * @author Harpreet Singh <harpreet.singh.hora@logicielsolutions.co.in>
 * if ($e->getCode() == 400) {
 *   throw new DropboxException("Bad request", 400);
 * };
 * if ($e->getCode() == 409) {
 *     throw new DropboxException("Invalid path or file/folder not found.", 409);
 * }
 * if ($e->getCode() == 401) {
 *     throw new DropboxException("Unauthorized.Please re-connect to dropbox.", 403);
 * };
 */

class DropboxService
{
    /**
     * Guzzle Http request.
     * @var GuzzleHttp\Client
     */
    protected $request;
    protected $resourcesService;
    protected $proposalService;
    protected $estimateService;
    protected $token;

    function __construct(ResourceServices $resourcesService, ProposalService $proposalService, EstimationService $estimateService)
    {
        $this->client = new Client(['base_uri' => config('dropbox.base_url')]);
        $this->downloadClient = new Client(['base_uri' => config('dropbox.download_base_url')]);

        $this->resourcesService = $resourcesService;
        $this->proposalService = $proposalService;
        $this->estimateService = $estimateService;
    }

    /**
     * Authentication
     * @param  string $username | Username
     * @param  string $password | Paswwrod
     * @return string $token
     */
    public function authentication()
    {
        try {
            $userId = \Auth::id();
            $companyId = getScopeId();
            $data = [
                'client_id' => config('dropbox.client_id'),
                'response_type' => 'code',
                'redirect_uri' => config('dropbox.redirect_url'),
                'state' => json_encode([
                    'company_id' => Crypt::encrypt($companyId),
                    'user_id' => Crypt::encrypt($userId)
                ])
            ];

            $url = config('dropbox.auth_url') . '?' . http_build_query($data);

            return \redirect($url);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function generateTokenByCode($code)
    {
        try {
            $data = [
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => config('dropbox.redirect_url'),
                'client_id' => config('dropbox.client_id'),
                'client_secret' => config('dropbox.client_secret'),
            ];
            $response = $this->client->request('POST', 'oauth2/token', [
                'form_params' => $data
            ]);
            $data = json_decode($response->getBody(), 1);
            $userData = $this->getCurrentUser($data['access_token']);

            return [
                'data' => $data,
                'user_data' => $userData,
            ];
        } catch (\Exception $e) {
            throw new DropboxException($e->getMessage());
        }
    }

    /**
     * Get List Of File/Folder Saved on DropBox
     * @param  Object $token Token
     * @param  $limit limit of data
     * @param  $meta
     * @return List of Files
     */
    public function getList($token, $limit, $meta)
    {
        try {
            if (ine($meta, 'next_page_token')) {
                $body = [
                    "cursor" => $meta['next_page_token'],
                ];

                $response = $this->client->request('POST', 
                    '2/files/list_folder/continue',
                    [
                        'json' => $body,
                        'headers' => [
                            "Authorization" => "Bearer {$token}",
                        ]
                    ]
                );
            } else {
                $body = [
                    "path" => ine($meta, 'parent') ? $meta['parent'] : "",
                    "limit" => (int)$limit,
                    // "include_media_info" => true,
                    // "include_mounted_folders" => true,
                ];

                $response = $this->client->request('POST', 
                    '2/files/list_folder',
                    [
                        'json' => $body,
                        'headers' => [
                            "Authorization" => "Bearer {$token}",
                        ]
                    ]
                );
            }

            $files = json_decode($response->getBody(), 1);

            // get thumbnail of an image
            $files['entries'] = $this->getThumb($files['entries'], $token);

            return $files;

            // $allowedExtensions =  array('gif','png' ,'jpg', 'jpeg');
            // $images['entries'] = [];
            // foreach ($files['entries'] as $file) {
            // 	$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            // 	if(!file_exists($file['name']) && !in_array($extension,$allowedExtensions)){
            // 		continue;
            // 	}else{
            // 		$data = [
            // 				"path"   => $file['path_display'],
            // 				"format" => "jpeg",
            // 				"size"   => "w128h128",
            // 				"mode"   => "fitone_bestfit",
            // 			];
            // 	    array_push($images['entries'], $data);
            // 	}
            // }

            // $response = $this->client->request('POST', config('dropbox.download_base_url').'2/files/get_thumbnail_batch',['json'=>$images]);
            // $thumbnailData = json_decode($response->getBody(), 1);
            // foreach ($files['entries'] as $key => $file) {
            // 	foreach ($thumbnailData['entries'] as $thumbnail) {
            // 		if(!isset($thumbnail['metadata']) || ($thumbnail['metadata']['id'] != $file['id'])) {
            // 			continue;
            // 		} else {
            // 			$files['entries'][$key]['thumbnail'] = $thumbnail['thumbnail'];
            // 		}
            // 	}
            // }
        } catch (\Exception $e) {
            if ($e->getCode() == 400) {
                throw new DropboxException("Bad request", 400);
            };
            if ($e->getCode() == 409) {
                throw new DropboxException("Invalid path or file/folder not found.", 409);
            }
            if ($e->getCode() == 401) {
                throw new DropboxException("Unauthorized.Please re-connect to dropbox.", 403);
            };

            // if($e->hasResponse()) {

            // 	throw new DropboxException($e->getResponse()->getBody()->getContents());
            // }

            throw $e;
        }
    }

    /**
     * Download file
     * @param  Object $token Token
     * @param  $meta
     * @return Content of File
     */
    // public function download($token, $fileId)
    // {
    // 	try {
    // 		$data = $this->getFileContents($token, $fileId);
    // 		return [
    //          'content' => $data['content'],
    // 			'name' 	  => $data['file']['name'],
    // 		];
    // 	} catch(DropboxFileSizeExceededException $e) {

    // 		throw $e;
    // 	} catch(Exception $e) {
    // 		if($e->getCode() == 400) {
    //          throw new DropboxException("Empty or Invalid Path", 400);
    // 		}
    // 		if($e->getCode() == 409) {
    // 			throw new DropboxException("File not Found", 409);
    // 		}
    // 		if($e->getCode() == 401) {
    // 			throw new DropboxException("Unauthorized.Please re-connect to dropbox.", 403);
    // 		};
    // 		// if($e->hasResponse()) {
    // 		// 	throw new DropboxException($e->getResponse()->getBody()->getContents());
    // 		// }

    // 		throw $e;
    // 	}
    // }

    /**
     * Save/Copy DropBox Files
     * @param Object $token Token
     * @param $fileId id of File
     * @param $meta
     * @return dataObject
     */
    public function saveFile($token, $fileId, $meta)
    {
        try {
            $data = $this->getFileContents($token, $fileId);
            $ext = pathinfo($data['file']['name'], PATHINFO_EXTENSION);
            $mimeType = getMimeTypeFromExt($ext);

            switch ($meta['save_as']) {
                // save to resources..
                case 'resource':
                    $dataObject = $this->resourcesService->createFileFromContents($meta['parent_id'], $data['content'], $data['file']['name'], $mimeType);
                    $transformer = new ResourcesTransformer;
                    break;

                // save to proposal..
                case 'proposal':
                    $dataObject = $this->proposalService->createFileFromContents($meta['job_id'], $data['content'], $data['file']['name'], $mimeType);
                    $transformer = new ProposalsTransformer;
                    break;

                // save to estimate..
                case 'estimate':
                    $dataObject = $this->estimateService->createFileFromContents($meta['job_id'], $data['content'], $data['file']['name'], $mimeType);
                    $transformer = new EstimationsTransformer;
                    break;

                default:
                    throw new Exception("Invalid Save type");
                    break;
            }

            return [
                'dataObject' => $dataObject,
                'transformer' => $transformer
            ];
        } catch (DropboxFileSizeExceededException $e) {
            throw $e;
        } catch (\Exception $e) {
            if($e->getCode() == 503) {
                throw new DropboxException("We are unable to connect to Dropbox currently. Please try after some time!", 503);  
            };
            if ($e->getCode() == 400) {
                throw new DropboxException("Bad request", 400);
            };
            if ($e->getCode() == 409) {
                throw new DropboxException("We are unable to connect to Dropbox currently. Please try after some time!", 409);
            }
            if ($e->getCode() == 401) {
                throw new DropboxException("Unauthorized.Please re-connect to dropbox.", 403);
            };

            // if($e->hasResponse()) {

            // 	throw new DropboxException($e->getResponse()->getBody()->getContents());
            // }

            throw $e;
        }
    }

    /**
     * search files & folders
     * @param  $token
     * @param  $input
     * @return $data
     */
    public function search($token, $input)
    {
        try {
            $data = [];
            $nextPageToken = null;

            $body = [
                "query" => $input['keyword'] ? $input['keyword'] : '',
                "path" => $input['parent'] ? $input['parent'] : '',
                "max_results" => config('jp.drop_box_max_search_limit'),
            ];

            if (ine($input, 'next_page_token')) {
                $body["start"] = (int)$input['next_page_token'];
            }

            $response = $this->client->request('POST', '2/files/search', [
                'json' => $body,
                'headers' => [
                    "Authorization" => "Bearer {$token}",
                ]
            ]);
            $response = json_decode($response->getBody(), 1);

            // get thumbnails
            $response['matches'] = $this->getThumb($response['matches']);

            foreach ($response['matches'] as $key => $value) {
                if (isset($value['metadata'])) {
                    $data[$key]['id'] = $value['metadata']['id'];
                    $data[$key]['name'] = $value['metadata']['name'];
                    $data[$key]['.tag'] = $value['metadata']['.tag'];
                    $data[$key]['size'] = isset($value['metadata']['size']) ? $value['metadata']['size'] : null;
                    $data[$key]['thumbnail'] = isset($value['thumbnail']) ? $value['thumbnail'] : null;
                }
            }

            if (isset($response['more']) && isTrue($response['more'])) {
                $nextPageToken = $response['start'];
            }

            return [
                'data' => $data,
                'next_page_token' => $nextPageToken
            ];
        } catch (\Exception $e) {
            if ($e->getCode() == 400) {
                throw new DropboxException("Bad request", 400);
            };
            if ($e->getCode() == 409) {
                throw new DropboxException("Invalid path or file/folder not found.", 409);
            }
            if ($e->getCode() == 401) {
                throw new DropboxException("Unauthorized.Please re-connect to dropbox.", 403);
            };

            throw $e;
        }
    }

    /**
     * get listing of shared folders
     * @param  $token authorization token
     * @return $data
     */
    public function listSharedFolders($token)
    {
        try {
            $data = [];
            $response = $this->client->request('POST', '2/sharing/list_folders', [
                'headers' => [
                    "Authorization" => "Bearer {$token}",
                ]
            ]);
            $response = json_decode($response->getBody(), 1);

            foreach ($response['entries'] as $key => $value) {
                $data[$key]['id'] = $value['shared_folder_id'];
                $data[$key]['name'] = $value['name'];
            }
        } catch (\Exception $e) {
            if ($e->getCode() == 400) {
                throw new DropboxException("Bad request", 400);
            };
            if ($e->getCode() == 409) {
                throw new DropboxException("Invalid path or file/folder not found.", 409);
            }
            if ($e->getCode() == 401) {
                throw new DropboxException("Unauthorized.Please re-connect to dropbox.", 403);
            };

            throw $e;
        }

        return $data;
    }

    /**
     * get list of shared files
     * @param  $token authorization token
     * @return $data
     */
    public function listSharedFiles($token, $input)
    {
        try {
            if (ine($input, 'next_page_token')) {
                $params = [
                    'cursor' => $input['next_page_token']
                ];

                $response = $this->client->request('POST', '2/sharing/list_received_files/continue', [
                    'json' => $params,
                    'headers' => [
                        "Authorization" => "Bearer {$token}",
                    ]
                ]);
            } else {
                $params = [
                    'limit' => ine($input, 'limit') ? (int)$input['limit'] : config('jp.pagination_limit'),
                ];

                $response = $this->client->request('POST', '2/sharing/list_received_files', [
                    'json' => $params,
                    'headers' => [
                        "Authorization" => "Bearer {$token}",
                    ]
                ]);
            }

            $response = json_decode($response->getBody(), 1);
            $data = [];

            foreach ($response['entries'] as $key => $value) {
                $data['entries'][$key]['id'] = $value['id'];
                $data['entries'][$key]['name'] = $value['name'];
                $data['entries'][$key]['.tag'] = 'file';
            }

            if (!isset($data['entries'])) {
                $data['entries'] = [];
            }

            $data['next_page_token'] = ine($response, 'cursor') ? $response['cursor'] : null;

            return $data;
        } catch (\Exception $e) {
            if ($e->getCode() == 400) {
                throw new DropboxException("Bad request", 400);
            };
            if ($e->getCode() == 409) {
                throw new DropboxException("Invalid path or file/folder not found.", 409);
            }
            if ($e->getCode() == 401) {
                throw new DropboxException("Unauthorized.Please re-connect to dropbox.", 403);
            };

            throw $e;
        }
    }

    /**
     * add shared folders to dropbox
     * @param  $token authorization token
     * @param  $input
     * @return $response
     */
    public function mountSharedFolders($token, $input)
    {
        try {
            $params = [
                'shared_folder_id' => ine($input, 'folder_id') ? $input['folder_id'] : '',
            ];

            $response = $this->client->request('POST', '2/sharing/mount_folder', [
                'json' => $params,
                'headers' => [
                    "Authorization" => "Bearer {$token}",
                ]
            ]);
        } catch (\Exception $e) {
            if ($e->getCode() == 400) {
                throw new DropboxException("Bad request", 400);
            };
            if ($e->getCode() == 409) {
                throw new DropboxException("Invalid path or file/folder not found.", 409);
            }
            if ($e->getCode() == 401) {
                throw new DropboxException("Unauthorized.Please re-connect to dropbox.", 403);
            };

            throw $e;
        }

        return json_decode($response->getBody(), 1);
    }

    /**
     * download file
     * @param  $token
     * @param  $input
     * @return $file
     */
    public function download($token, $input)
    {
        try {
            $fileId = ine($input, 'file_id') ? $input['file_id'] : '';
            $file = $this->getFileContents($token, $fileId);

            return $file;
        } catch (\Exception $e) {
            if($e->getCode() == 503) {
                throw new DropboxException("We are unable to connect to Dropbox currently. Please try after some time!", 503);
            };
            if ($e->getCode() == 400) {
                throw new DropboxException("Bad request", 400);
            };
            if ($e->getCode() == 409) {
                throw new DropboxException("Invalid path or file/folder not found.", 409);
            }
            if ($e->getCode() == 401) {
                throw new DropboxException("Unauthorized.Please re-connect to dropbox.", 403);
            };

            throw $e;
        }
    }


    /**************** Private Section ********************/

    /**
     * getFileContents
     * @param  $token  Authorization Token
     * @param  int $fileId Id of file
     *
     * @return file contents
     */
    private function getFileContents($token, $fileId)
    {
        $body = [
            "path" => $fileId,
        ];
        $params = [
            "headers" =>[
                "Authorization" => "Bearer {$token}",
                "Dropbox-API-Arg" => json_encode($body),
            ],
        ];
        // $this->client->setDefaultOption('headers',[
        //     "Authorization" => "Bearer {$token}",
        //     "Dropbox-API-Arg" => json_encode($body),
        // ]);
        $response = $this->downloadClient->request('POST', '2/files/download', $params);
        $data = $response->getHeaderLine('dropbox-api-result');
        $file = json_decode($data, true);
        $size = $file['size'];
        if(!is_null($size) && ($size > 20971520)) {
            throw new DropboxFileSizeExceededException("Unable to download file more than 20 MB.");
        }
        $content = $response->getBody()->getContents();
        return [
            'content' => $content,
            'file'    => $file,
        ];
    }

    private function getCurrentUser($token)
    {
        try {
            $response = $this->client->request('POST', '2/users/get_current_account', [
                'headers' => [
                    "Authorization" => "Bearer {$token}",
                ]
            ]);

            return json_decode($response->getBody(), 1);
        } catch (\Exception $e) {
            if ($e->hasResponse()) {
                $errorResponse = $e->getResponse()->json();
                throw new DropboxException($errorResponse['error_description']);
            }

            throw $e;
        }
    }

    /**
     * get thumbnail of images
     *
     * @param  $files Response from dropbox
     *
     * @return $files With thumbnails
     */
    private function getThumb($files, $token)
    {
        $allowedExtensions = ['gif', 'png', 'jpg', 'jpeg'];
        $images['entries'] = [];
        foreach ($files as $file) {
            if (isset($file['metadata'])) {
                $fileName = $file['metadata']['name'];
                $filePath = $file['metadata']['path_display'];
            } else {
                $fileName = $file['name'];
                $filePath = $file['path_display'];
            }

            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
            if (!file_exists($fileName) && !in_array($extension, $allowedExtensions)) {
                continue;
            } else {
                $data = [
                    "path" => $filePath,
                    "format" => "jpeg",
                    "size" => "w128h128",
                    "mode" => "fitone_bestfit",
                ];
                array_push($images['entries'], $data);
            }
        }


        $response = $this->downloadClient->request('POST', '2/files/get_thumbnail_batch', ['json' => $images, 'headers' => [ "Authorization" => "Bearer {$token}"]]);
        $thumbnailData = json_decode($response->getBody(), 1);
        foreach ($files as $key => $file) {
            if (isset($file['metadata'])) {
                $fileId = $file['metadata']['id'];
            } else {
                $fileId = $file['id'];
            }

            foreach ($thumbnailData['entries'] as $thumbnail) {
                if (!isset($thumbnail['metadata']) || ($thumbnail['metadata']['id'] != $fileId)) {
                    continue;
                } else {
                    $files[$key]['thumbnail'] = $thumbnail['thumbnail'];
                }
            }
        }

        return $files;
    }
}
