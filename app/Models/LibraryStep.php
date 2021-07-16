<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LibraryStep extends Model
{

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'library_steps';


    public static function getActiveLibrarySteps()
    {
        $library_steps = LibraryStep::where('status', true)->get();
        return $library_steps;
    }
}
