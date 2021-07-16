<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class GmailThreadTransformer extends TransformerAbstract
{

    public function transform($resource)
    {

        return [
            'thread_id' => issetRetrun($resource, 'thread_id') ?: null,
            'message_id' => isset($resource['content']['message_id']) ? $resource['content']['message_id'] : null,
            'subject' => isset($resource['content']['subject']) ? $resource['content']['subject'] : '',
            'from' => isset($resource['content']['from']) ? $resource['content']['from'] : '',
            'reply_to' => isset($resource['content']['reply_to']) ? $resource['content']['reply_to'] : '',
            'to' => isset($resource['content']['to']) ? $resource['content']['to'] : '',
            'cc' => isset($resource['content']['cc']) ? $resource['content']['cc'] : '',
            'date' => isset($resource['content']['date']) ? dateTimeParse($resource['content']['date'], 'Y-m-d h:i:s', 'UTC') : null,
            'is_read' => isset($resource['is_read']) ? $resource['is_read'] : true,
            'content' => isset($resource['content']['content']) ? $resource['content']['content'] : '',
            'attachment' => isset($resource['content']['attachment']) ? $resource['content']['attachment'] : [],
        ];
    }
}
