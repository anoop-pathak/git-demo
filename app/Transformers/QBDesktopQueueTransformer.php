<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class QBDesktopQueueTransformer extends TransformerAbstract
{

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
	public function transform($queue)
    {
        $customErrorMsg = $queue->msg;

        if($queue->custom_error_msg) {
            $customErrorMsg = $queue->custom_error_msg;
        }

        return [
            'id'     => $queue->quickbooks_queue_id,
            'status' => $queue->qb_status,
            'msg'    => $queue->msg,
            'custom_error_msg' => $customErrorMsg,
        ];

	}
}