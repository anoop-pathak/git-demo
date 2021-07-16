<?php

return [


    'pdf' => [
        'enabled' => true,
        'binary' => env('WKHTML_TO_PDF_PATH'),
        'timeout' => false,
        'options' => [],
    ],
    'image' => [
        'enabled' => true,
        'binary' => env('WKHTML_TO_IMAGE_PATH'),
        'timeout' => false,
        'options' => [],
    ],


];
