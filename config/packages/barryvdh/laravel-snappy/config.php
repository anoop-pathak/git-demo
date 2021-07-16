<?php

return array(


    'pdf' => array(
        'enabled' => true,
        'binary' => env('WKHTML_TO_PDF_PATH', 'wkhtmltopdf'),
        'options' => array(),
    ),
    'image' => array(
        'enabled' => true,
        'binary' => env('WKHTML_TO_IMAGE_PATH', 'wkhtmltoimage'),
        'options' => array(),
    ),
);
