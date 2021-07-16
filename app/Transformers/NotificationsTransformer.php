<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class NotificationsTransformer extends TransformerAbstract
{

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = ['object'];

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
    public function transform($notification)
    {
        return [
            'id' => $notification->id,
            'subject' => $notification->subject,
            'body' => $notification->body,
            'object_type' => $notification->object_type,
            'sent_at' => $notification->sent_at,
            'sender_id' => $notification->sender_id,
            'created_at' => $notification->created_at,
        ];
    }

    /**
     * Include Object
     *
     * @return League\Fractal\ItemResource
     */
    public function includeObject($notification)
    {
        try {
            $object = $notification->getObject();
            if ($notification->object_type == 'Job') {
                return $this->item($object, new JobsTransformer);
            } else {
                return $this->item($object, function ($object) {
                    return $object->toArray();
                });
            }
        } catch (\Exception $e) {
            $array = [];
            return $this->item($array, function ($array) {
                return $array;
            });
        }
    }
}
