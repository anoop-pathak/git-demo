<?php

namespace App\Repositories;

use App\Models\JobContact;

class JobContactRepository extends AbstractRepository
{

    /**
     * The base eloquent model
     * @var Eloquent
     */
    protected $model;
    protected $scope;

    public function __construct(JobContact $model)
    {
        $this->model = $model;
    }

    public function saveJobContact($input, $jobId)
    {
        $jobContact = $this->model->whereJobId($jobId)->first();
        if (!$jobContact) {
            $input['job_id'] = $jobId;
            $jobContact = JobContact::create($input);
        } else {
            $jobContact->update($input);
        }
        return $jobContact;
    }
}
