<?php

namespace App\Repositories;

use App\Events\LostJob;
use App\Exceptions\NotFoundException;
use App\Models\ActivityLog;
use App\Models\JobFollowUp;
use App\Services\Contexts\Context;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;

class JobFollowUpRepository extends ScopedRepository
{

    /**
     * The base eloquent model
     * @var Eloquent
     */
    protected $model;
    protected $scope;

    public function __construct(JobFollowUp $model, Context $scope)
    {
        $this->model = $model;
        $this->scope = $scope;
    }

    public function saveFollowUp($customerId, $jobId, $stageCode, $note, $mark, $meta = [])
    {

        $followUp = JobFollowUp::create([
            'customer_id' => $customerId,
            'job_id' => $jobId,
            'stage_code' => $stageCode,
            'note' => $note,
            'mark' => $mark,
            'date_time' => isset($meta['date_time']) ? $meta['date_time'] : null,
            'task_id' => isset($meta['task_id']) ? $meta['task_id'] : null,
            'order' => $this->getOrder($jobId, $mark),
            'created_by' => \Auth::id(),
            'company_id' => $this->scope->id()
        ]);

        $this->setActive($followUp, $jobId);

        $this->setActivityLog($followUp);
        $followUp->job->updateJobUpdatedAt();

        return $followUp;
    }

    public function getFilteredFollowUps($filters, $sortable = true)
    {
        $followUps = $this->getFollowUps($sortable);
        $this->applyFilter($followUps, $filters);
        return $followUps;
    }

    public function getFollowUps($sortable = true)
    {
        $followUps = null;

        if ($sortable) {
            $followUps = $this->make(['user', 'stage'])->Sortable()
                ->orderBy('id', 'desc');
        } else {
            $followUps = $this->make(['user', 'stage'])
                ->orderBy('id', 'desc');
        }

        return $followUps;
    }

    /**
     * job follow up delete
     * @param  JobFollowUp $followUp [object]
     * @return [type]                [description]
     */
    public function delete(JobFollowUp $followUp)
    {
        $followUp->delete();
        $latestFollowUp = JobFollowUp::latestFollowUp($followUp->job_id)->first();

        if ($latestFollowUp) {
            $latestFollowUp->update(['active' => true]);
        }

        $followUp->job->updateJobUpdatedAt();

        if ($followUp->mark === 'lost_job') {
            Event::fire(
                'JobProgress.Customers.Events.LostJobEventHandler',
                new LostJob($followUp, ActivityLog::LOST_JOB_RESTATE)
            );
        }

        if ($latestFollowUp && $latestFollowUp->mark === 'lost_job') {
            Event::fire(
                'JobProgress.Customers.Events.LostJobEventHandler',
                new LostJob($latestFollowUp)
            );
        }
    }

    /*********************** Private function ***************************/

    private function applyFilter($query, $filters)
    {
        if (ine($filters, 'customer_id')) {
            $query->where('customer_id', '=', $filters['customer_id']);
        }

        if (ine($filters, 'job_id')) {
            $query->where('job_id', '=', $filters['job_id']);
        }

        if (ine($filters, 'stage_code')) {
            $query->where('stage_code', '=', $filters['stage_code']);
        }

        if (ine($filters, 'mark')) {
            $mark = $filters['mark'];
            $query->where(function ($query) use ($mark) {
                switch ($mark) {
                    case 'call1':
                        $query->where('mark', 'call')->where('order', 1);
                        break;
                    case 'call2':
                        $query->where('mark', 'call')->where('order', 2);
                        break;
                    case 'call3_or_more':
                        $query->where('mark', 'call')->where('order', '>', 2);
                        break;
                    case 'undecided':
                        $query->where('mark', 'undecided');
                        break;
                    case 'lost_job':
                        $query->where('mark', 'lost_job');
                        break;
                    case 'reminder':
                        $query->whereNotNull('task_id');
                        break;
                    case 'no_action_required':
                        $query->where('mark', 'no_action_required');
                        break;
                }
                $query->orWhere('mark', 'completed');
            });
        }
    }

    private function getOrder($jobId, $mark)
    {
        $followUp = JobFollowUp::whereMark($mark)->whereJobId($jobId)->latest()->first();

        if (!$followUp) {
            return 1;
        }
        $followUp->active = false;
        $followUp->save();
        $order = $followUp->order;
        return $order + 1;
    }

    private function setActive($currentFollowUp, $jobId)
    {
        $followUp = JobFollowUp::whereJobId($jobId)->where('id', '<', $currentFollowUp->id)->latest()->update(['active' => false]);
        $currentFollowUp->active = true;
        $currentFollowUp->save();
    }

    private function getLatestFollowUp($jobId)
    {
        return $this->setLatestFollowUpDeActiveByJobId($jobId);
    }

    private function setLatestFollowUpDeActiveByJobId($jobId)
    {
        $followUp = $this->whereJobId($jobId)->latest()->first();
        if (!$followUp) {
            throw new NotFoundException("Job Follow up not found");
        }
        $followUp = $followUp->update(['active' => 0]);
        return $followUp;
    }

    private function setActivityLog($followUp)
    {
        $oldFollowUp = JobFollowUp::whereJobId($followUp->job_id)
            ->where('id', '<', $followUp->id)
            ->latest()
            ->first();

        if (($followUp->mark === 'lost_job')) {
            Event::fire('JobProgress.Customers.Events.LostJobEventHandler', new LostJob($followUp));
        }

        if ($followUp->mark != 'lost_job'
            && ($oldFollowUp)
            && $oldFollowUp->mark === 'lost_job') {
            Event::fire(
                'JobProgress.Customers.Events.LostJobEventHandler',
                new LostJob($followUp, ActivityLog::LOST_JOB_RESTATE)
            );
        }
    }
}
