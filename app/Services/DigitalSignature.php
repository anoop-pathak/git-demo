<?php
namespace App\Services;

use GuzzleHttp\Client;
use FlySystem;
use App\Models\Resource;

class DigitalSignature
{
	public function authorizeFile($path, $fileContent)
	{
		$pathInfo = explode('/', $path);
		$fileName = end($pathInfo);
		$tempPath = config('jp.BASE_PATH_PUBLIC').'temp/'.$path;

		// save file on local system
		FlySystem::connection('local')->put($tempPath, $fileContent, ['Content-Type' => 'application/pdf']);

		$guzzle = new Client;
		$file = fopen(base_path($tempPath), 'r');
		$url  = config('consigno-server.url');
		$json = config('consigno-server.json');
		$json['out'] = "'$fileName'";

		$data = [
			'file' => $file,
			'json' => json_encode($json),
		];

		$res = $guzzle->post($url, ['body' => $data]);
		$companyRoot = Resource::companyRoot(getScopeId());

		$digitalFilePath = $companyRoot->path.'/'.config('resources.DIGITAL_AUTHORIZED_DIR').'/'.$fileName;
		$signedFileFullPath = config('resources.BASE_PATH').$digitalFilePath;

		FlySystem::connection('s3')->put($signedFileFullPath, $res->getBody()->getContents(), ['ContentType' => 'application/pdf']);

		// delete temp file
		FlySystem::connection('local')->delete($tempPath);

		$data = [
			'file_path' => $signedFileFullPath,
			'file_size' => FlySystem::connection('s3')->getSize($signedFileFullPath),
		];

		return $data;
	}
}
