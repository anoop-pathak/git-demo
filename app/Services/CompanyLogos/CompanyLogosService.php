<?php

namespace App\Services\CompanyLogos;

use App\Models\CompanyLogo as CompanyLogoModel;
use App\Models\Company;
use Carbon\Carbon;
use Config;
use FlySystem;
use Image;

class CompanyLogosService {

	public function getLogos()
	{
		$companyId = getScopeId(); 
		$logos = CompanyLogoModel::where('company_id', $companyId)->firstOrFail();
		return $logos;
	}
	
	public function uploadAndUpdateLogo($logo, $type)
	{
		$companyId = getScopeId();
		
		$filename  = $companyId.'_'.Carbon::now()->timestamp.'.jpg';
		$baseName = 'company/logos/'. $filename;
		$fullpath = Config::get('jp.BASE_PATH').$baseName;
		$this->uploadLogo($logo, $fullpath);
		
		$companyLogo = CompanyLogoModel::firstOrNew(array('company_id' => $companyId));
		$companyLogo->{$type} = $baseName;
		$companyLogo->save();
		return $companyLogo;
	}
	
	public function removeLogos()
	{
		$companyId = getScopeId();
		$logo = CompanyLogoModel::where('company_id', $companyId)->firstOrFail();
		return $logo->delete();
	}
	
	public function deleteLogosWithFiles($logo)
	{
		$smallLogo = $this->getFullPathOfLogo($logo->small_logo);
		$largeLogo = $this->getFullPathOfLogo($logo->large_logo);
		
		if(FlySystem::exists($smallLogo) && $logo->small_logo) {
			FlySystem::delete($smallLogo);
		}
		if(FlySystem::exists($largeLogo) && $logo->large_logo) {
			FlySystem::delete($largeLogo);
		}
		return $logo->delete();
	}
	
	private function uploadLogo($image, $fullpath)
	{
		$image = Image::make($image);
		
		return FlySystem::uploadPublicaly($fullpath, $image->encode()->getEncoded());
	}
	
	private function getFullPathOfLogo($logoBaseName)
	{
		return Config::get('jp.BASE_PATH').$logoBaseName;
	}
}