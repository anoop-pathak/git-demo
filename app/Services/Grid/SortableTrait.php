<?php

namespace App\Services\Grid;

use Illuminate\Support\Facades\DB;
use Request;

trait SortableTrait
{

    public function scopeSortable($query)
    {
        if (Request::has('sort_by') && Request::has('sort_order')) {
            $sortBy = Request::get('sort_by');
            $sortOrder = Request::get('sort_order');

            if ($sortBy == 'id') {
                $query->select(['*']);
            } else {
                $query->select(['*', \DB::raw("IF($sortBy IS NOT NULL, $sortBy, 'null')")]);
            }

            return $query->orderBy($sortBy, $sortOrder);
        } else {
            return $query;
        }
    }
}
