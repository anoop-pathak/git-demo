<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Register read filters
    |--------------------------------------------------------------------------
    */

    'registered'    =>  [
        'chunk' => 'Maatwebsite\Excel\Filters\ChunkReadFilter'
    ],

    /*
    |--------------------------------------------------------------------------
    | Enable certain filters for every file read
    |--------------------------------------------------------------------------
    */

    'enabled'   =>  []

];
