<?php
    
return [

    /*
	|--------------------------------------------------------------------------
	| Resources Base Path
	|--------------------------------------------------------------------------
	*/
    
    'BASE_PATH' => 'resources/',

    /*
	|--------------------------------------------------------------------------
	| Base directories of resources
	|--------------------------------------------------------------------------
	*/
    'BASE_DIRECTORIES' => [
        
        'Management',
        'Operations',
        'Sales',
        'Office',
        'Jobs',
        'emails_attachments',
        'labours',
    ],

    /*
	|--------------------------------------------------------------------------
	| Job resources directories
	|--------------------------------------------------------------------------
	*/
    'JOB_RESOURCES' => [

        [
            'name' => 'Documents',
            'locked' => false
        ],
        [
            'name' => 'Photos',
            'locked' => true
        ],
        [
            'name' => 'Estimates',
            'locked' => false
        ],
        [
            'name' => 'Other',
            'locked' => false
        ],
    ],

    /*
	|--------------------------------------------------------------------------
	| Default permision mode
	|--------------------------------------------------------------------------
	*/
    'DEFAULTE_MODE' => 0755,

    /*
	|--------------------------------------------------------------------------
	| Thumb size setting
	|--------------------------------------------------------------------------
	*/
    'thumb_size' => [

        'height' => 300,
        'width'  => 300
    ],

    /*
	|--------------------------------------------------------------------------
	| Valide MIME types of images
	|--------------------------------------------------------------------------
	*/
    'image_types' => [

        'image/jpeg',
        'image/jpg',
        'image/png',
    ],

    /*
	|--------------------------------------------------------------------------
	| Valide MIME types of documents
	|--------------------------------------------------------------------------
	*/
    'docs_types' => [

        'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/vnd.ms-powerpoint',
        'application/msword',
        'text/plain',
        'application/vnd.ms-excel',
        // 'application/vnd.ms-office',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',

        // zip or compressed files
        'application/zip',
        'application/rar',
        // 'multipart/x-gzip',
        // 'multipart/x-zip',
        'application/x-zip',
        'application/x-rar',
        // 'application/x-gzip',
        'application/x-zip-compressed',
        'application/x-rar-compressed',
        // 'application/octet-stream',
        
        // Ai files
        'application/postscript',
        'vnd.adobe.illustrator',
        'application/pdf',

        //email
        'message/rfc822',

        //psd files
        'image/vnd.adobe.photoshop',
        'image/photoshop',
        'image/x-photoshop',
        'image/psd',
        'application/photoshop',
        'application/psd',
        'zz-application/zz-winassoc-psd',

        // eps files
        'application/postscript',
        'application/eps',
        'application/x-eps',
        'image/eps',
        'image/x-eps',

        //dxf files
        'text/plain',
        'image/vnd.dxf',
        'image/x-dwg',
        'application/dxf',
        'image/vnd.dwg',

        //skp files
        'application/x-koan',
        'application/vnd.koan',
        'application/vnd.sketchup.skp',

        //ac5, ac6,skp,ve files
        'application/octet-stream',

        // sdr file
        'application/sounder',

        //iwork files
		'application/vnd.apple.pages',
        'application/vnd.apple.numbers',
    ],

    /*
	|--------------------------------------------------------------------------
	| Valide MIME types of Excel
	|--------------------------------------------------------------------------
	*/
    'excel_types' => [
        'text/plain',
        'application/vnd.ms-excel',
        // 'application/vnd.ms-office',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.apple.numbers',
    ],

    /*
	|--------------------------------------------------------------------------
	| Valide MIME types of Compressed files
	|--------------------------------------------------------------------------
	*/
    'compressed_file_types' => [
        'application/zip',
        'application/x-zip',
        'application/x-zip-compressed',
        'application/rar',
        'application/x-rar',
        'application/x-rar-compressed',
    ],

    /*
	|--------------------------------------------------------------------------
	| Valide MIME types of Word 
	|--------------------------------------------------------------------------
	*/
    'word_types' => [
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/msword',
    ],

    /*
	|--------------------------------------------------------------------------
	| Valide MIME types of Pdf
	|--------------------------------------------------------------------------
	*/
    'pdf_types' => [
        'application/pdf',
    ],

    /*
	|--------------------------------------------------------------------------
	| Valide MIME types of PowerPoint
	|--------------------------------------------------------------------------
	*/
    'powerpoint_types' => [
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/vnd.ms-powerpoint',
    ],

    /*
	|--------------------------------------------------------------------------
	| Valide MIME types of Text Type 
	|--------------------------------------------------------------------------
	*/
    'text_types' => [
        'text/plain',
    ],

    'multi_image_width' => [768, 1024, 1600],

    'DIGITAL_AUTHORIZED_DIR' => 'digitally_authorized',
];
