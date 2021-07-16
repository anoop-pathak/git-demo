<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class QuickbooksActivityTransformer extends TransformerAbstract {

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
    protected $availableIncludes = ['entity_logs'];

     /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($activity) {
       $data = [
            'customer_id'              =>   $activity->customer_id,
            'msg'                      =>   $activity->msg,
            'type'                     =>   $activity->type,
            'created_at'               =>   $activity->created_date,
            'group_count'              =>   $activity->group_count,
        ];

        return $data;
    }

    /**
     * Include entity logs
     *
     * @return League\Fractal\ItemResource
     */
    public function includeEntityLogs($activity)
    {
        $entities = $activity->entities;
        if($entities){
            return $this->collection($entities, function($entity){
                return [
                    'id'                =>   $entity->entity_id,
                    'customer_id'       =>   $entity->customer_id,
                    'entity'            =>   $entity->entity,
                    'msg'               =>   $entity->msg,
                    'type'              =>   $entity->type,
                    'created_at'        =>   $entity->created_at,
                    'action'            =>   $entity->action,
                ];
            });
        }
    }
}
