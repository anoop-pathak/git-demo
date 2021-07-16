<?php
namespace App\Transformers\Optimized;

use League\Fractal\TransformerAbstract;

class ServerlessUserInfoTransformer extends TransformerAbstract
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
    protected $availableIncludes = [];

    public function transform($user) {
        return [
            'id'                => (int) $user->id,
            'company_id'        => (int) $user->company_id,
            'first_name'        => $user->first_name,
            'last_name'         => $user->last_name,
            'full_name'         => $user->full_name,
            'email'             => $user->email,
        ];
    }
}