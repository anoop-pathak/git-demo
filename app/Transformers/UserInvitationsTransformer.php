<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use App\Transformers\CompaniesTransformer;

class UserInvitationsTransformer extends TransformerAbstract
{
	/**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [];
	/**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = ['company'];

	public function transform($invitation)
	{
		return [
			'id'	 => $invitation->id,
			'status' => $invitation->status,
		];
    }

	public function includeCompany($invitation)
	{
		$company = $invitation->company;
		if($company) {
			return $this->item($company, new CompaniesTransformer);
		}
	}
}