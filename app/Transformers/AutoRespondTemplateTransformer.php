<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class AutoRespondTemplateTransformer extends TransformerAbstract
{

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($template)
    {
        return [
            'id' => $template->id,
            'active' => (bool)$template->active,
            'subject' => $template->subject,
            'content' => $template->content,
            'user_id' => $template->user_id,
        ];
    }
}
