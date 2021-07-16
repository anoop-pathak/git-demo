<?php 

namespace App\Transformers;

use FlySystem;
use League\Fractal\TransformerAbstract;

class CompanyLogoTransformer extends TransformerAbstract {
	 /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [];
	public function transform($companyLogo){
		
        return [
            'small'   => $companyLogo->small_logo ? FlySystem::publicUrl(config('jp.BASE_PATH'). $companyLogo->small_logo) : null,
			'large'   => $companyLogo->large_logo ? FlySystem::publicUrl(config('jp.BASE_PATH'). $companyLogo->large_logo) : null 
		];
	}
}