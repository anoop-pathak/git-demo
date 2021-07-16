<?php
return [

    'url' => env('SKYMEASURE_URL', ''),

    /*
	|--------------------------------------------------------------------------
	| Order Statuses
	|--------------------------------------------------------------------------
	|
	| Skymeasurorder statuses with status code
	|
	*/

    'status' => [
        0 => 'new',
        // 1	=>	'in_progress', // code 1 is not for inProgress
        3 => 'completed',
        4 => 'cancelled',
        22 => 'hold',
        41 => 'refunded',
        64 => 'partial_refund',
    ],

    'source_id' => env('SKYMEASURE_SOURCE_ID', ''), // ordersource id and customer type
];
