<?php
namespace App\Transformers\Optimized;

use League\Fractal\TransformerAbstract;
use App\Transformers\AddressesTransformer;

class DivisionTransformer extends TransformerAbstract
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
    protected $availableIncludes = ['address'];
    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($division)
    {
        return [
            'id' => $division->id,
            'name' => $division->name,
            'email'     => $division->email,
            'phone'     => $division->phone,
            'phone_ext' => $division->phone_ext,
            'color'     => $division->color,
            'code'      => $division->code,
        ];
    }
    /**
     * Include address
     *
     * @return League\Fractal\ItemResource
     */
    public function includeAddress($division)
    {
        $address = $division->address;
        if($address){
            return $this->item($address, new AddressesTransformer);
        }
    }
}