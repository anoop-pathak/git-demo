<?php
namespace App\Services;

use App\Repositories\FinancialProductImageRepository;
use FlySystem;

class FinancialProductImage {

    function __construct(FinancialProductImageRepository $repo)
    {
		$this->repo = $repo;
	}

    public function saveImage($productId, $file)
	{
		if (!$file->isValid()) {
            return false;
        }

		$companyId = getScopeId();
		$baseName = 'financial_products/images';
		$fullPath = config('jp.BASE_PATH').$baseName;
		$originalName = $file->getClientOriginalName();
		$physicalName = generateUniqueToken().'_'.$originalName;
		$size = $file->getSize();
		$mimeType = $file->getMimeType();

        $image = \Image::make($file)->orientate();
		FlySystem::put($fullPath.'/'.$physicalName, $image->encode()->getEncoded(), ['ContentType' => $mimeType]);
		$this->generateThumb($fullPath.'/'.$physicalName, $image);
		$meta['thumb_exists'] = true;
		$productImage = $this->repo->save($companyId, $productId, $baseName, $originalName, $mimeType, $size, $physicalName, $meta);

        return $productImage;
	}

    /**
	 * generate thumb of images
	 * @param  $thumbPath
	 * @param  $fileContent
	 * @return boolean
	 */
	public function generateThumb($filePath, $fileContent)
	{
		$fullPathThumb	= preg_replace('/(\.gif|\.jpg|\.png|\.jpeg)/i', '_thumb$1', $filePath);
		$image = \Image::make($fileContent)->orientate();
		if($image->height() > $image->width()) {
			$image->heighten(200, function($constraint) {
				$constraint->upsize();
			});
		}else {
			$image->widen(200, function($constraint) {
				$constraint->upsize();
			});
        }

		FlySystem::put($fullPathThumb, $image->encode()->getEncoded());

        return true;
	}

    public function deleteImage($image)
	{
		$oldFilePath = null;
		$oldThumbPath = null;
		if(!empty($image->path)) {
			$oldFilePath = config('jp.BASE_PATH').$image->path;

            if($image->thumb_exists){
				$oldThumbPath	= preg_replace('/(\.gif|\.jpg|\.png|\.jpeg)/i', '_thumb$1', $oldFilePath);
			}
		}
		$image->delete();
		$this->fileDelete($oldFilePath, $oldThumbPath);
	}

    /**
	 * File delete
	 * @param  url $oldFilePath  Old file Path Url
	 * @param  url $oldFilePath  Old thumb Path Url
	 * @return Boolan
	 */
	private function fileDelete($oldFilePath, $oldThumbPath)
	{
		if(!$oldFilePath) {
            return false;
        }

		try {
			FlySystem::delete($oldFilePath);
			FlySystem::delete($oldThumbPath);
		} catch(\Exception $e) {
			// nothing to do.
		}
	}
}