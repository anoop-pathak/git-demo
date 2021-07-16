<?php 
namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class HoverUserTransformer extends TransformerAbstract
{
 	/**
     * List of resources to automatically include
     *
     * @var array
     */
    // protected $defaultIncludes = [];
 	/**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [];
     /**
     * Turn this item object into a generic array
     *
     * @return array
     */
     public function transform($user){

       return [
          'id'   => $user['id'],
          'first_name' => $user['first_name'],
          'last_name'  => $user['last_name'],
          'email'      => $user['email'],
          'aasm_state' => $user['aasm_state'],
          'acl_template' => $user['acl_template'],
      ];
  }
} 