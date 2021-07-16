<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class DropboxTransformer extends TransformerAbstract
{

    public function transform($file)
    {
        return [
            'id' => $file['id'],
            'name' => $file['name'],
            'tag' => isset($file['.tag']) ? $file['.tag'] : null,
            'size' => isset($file['size']) ? $file['size'] : null,
            'thumb' => isset($file['thumbnail']) ? $file['thumbnail'] : null,
        ];
    }
}
