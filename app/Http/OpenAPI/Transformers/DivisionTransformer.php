<?php
namespace App\Http\OpenAPI\Transformers;
use League\Fractal\TransformerAbstract;

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
            'id'        => $division->id,
            'name'      => $division->name,
            'color'     => $division->color,
            'qb_id'     => $division->qb_id,
            'email'     => $division->email,
            'phone'     => $division->phone,
            'phone_ext' => $division->phone_ext,
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
