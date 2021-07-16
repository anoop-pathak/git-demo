<?php

namespace App\Repositories;

use App\Services\JobCustomStep;

class CustomStepRepository extends AbstractRepository
{


    /**
     * The base eloquent model
     * @var Eloquent
     */
    protected $model;

    function __construct(JobCustomStep $model)
    {
        $this->model = $model;
    }

    public function save($attributes)
    {
        $customStep = JobCustomStep::save_custom($attributes);
        return $customStep;
    }
}
