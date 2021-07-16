<?php

namespace App\Repositories;

use App\Models\Role;
use App\Models\User;
use App\Services\Contexts\Context;
use Illuminate\Support\Facades\DB;

class LabourRepository extends ScopedRepository
{
    /**
     * The base eloquent model
     * @var Eloquent
     */
    protected $model;
    protected $scope;

    function __construct(User $model, Context $scope)
    {
        $this->scope = $scope;
        $this->model = $model;
    }


    /**
     * @ return all labour or sub_contractor
     */
    public function getLabours($filters = [])
    {
        $with = $this->getIncludes($filters);
        // labors only..
        $labours = $this->make($with)
            ->onlySubContractors()
            ->orderBy('id', 'desc');

        $labours = $this->applyFilters($labours, $filters);

        return $labours;
    }

    /**
     * Assign Trades
     * @param  User $user | User object
     * @param  array $trades | Trades array
     * @return [type]         [description]
     */
    public function assignTrades(User $labor, $trades = [])
    {
        $labor->laborTrades()->detach();
        if (!empty($trades)) {
            $labor->laborTrades()->attach($trades);
        }

        return $labor;
    }

    public function getById($id, array $with = [])
    {
        // labors only
        $query = $this->make($with)->onlySubContractors();

        return $query->findOrFail($id);
    }

    /**
     * Assign Work types
     * @param  User $labor [User object]
     * @param  array $workTypes [WorkTypes array]
     * @return [type]            [description]
     */
    public function assigWorkTypes(User $labor, $workTypes = [])
    {
        $labor->laborWorkTypes()->detach();
        if (!empty($workTypes)) {
            $labor->laborWorkTypes()->attach($workTypes);
        }

        return $labor;
    }

    /**
     * **
     * @param  [int] $oldGroup [description]
     * @param  [object] $labour   [description]
     * @return [type]           [description]
     */
    public function groupChange($oldGroup, $labour)
    {
        if ($oldGroup == $labour->group_id) {
            return $labour;
        }
        set_time_limit(0);
        if ($labour->group_id == User::GROUP_SUB_CONTRACTOR) {
            // assign role..
            $role = Role::byName('sub-contractor');
            $labour->detachRole($role);
            $labour->attachRole($role);
            $labour->jobsAsLabor()->chunk(100, function ($jobLabor) use ($labour) {
                $data = [];
                foreach ($jobLabor as $key => $jl) {
                    $data[] = [
                        'sub_contractor_id' => $labour->id,
                        'job_id' => $jl->job_id,
                        'schedule_id' => $jl->schedule_id,
                        'work_crew_note_id' => $jl->work_crew_note_id,
                    ];
                }
                DB::table('job_sub_contractor')->insert($data);
            });
            $labour->jobsAsLabor()->detach();
        } else {
            $labour->jobsAsSubContractor()->chunk(100, function ($jobLabor) use ($labour) {
                $data = [];
                foreach ($jobLabor as $key => $jl) {
                    $data[] = [
                        'labour_id' => $labour->id,
                        'job_id' => $jl->job_id,
                        'schedule_id' => $jl->schedule_id,
                        'work_crew_note_id' => $jl->work_crew_note_id,
                    ];
                }
                DB::table('job_labour')->insert($data);
            });
            $labour->jobsAsSubContractor()->detach();
            $labour->laborTrades()->detach();
            $labour->laborWorkTypes()->detach();
            $labour->update(['hire_date' => null, 'note' => null]);
        }

        return $labour;
    }
    /**
     * get sub contractor by id
     * @param  $id
     * @return $subContractor
     */
    public function getSubById($id)
    {
        $subContractor = $this->make()
            ->where('id', $id)
            ->onlySubContractors()
            ->firstOrFail();
        return $subContractor;
    }

    /** Private Functions **/

    private function applyFilters($query, $filters)
    {
        $query->division();

        if(!ine($filters, 'with_deactivated')
		 	&& !ine($filters, 'only_deactivated')) {
            $query->whereActive(true);
        }

        if (ine($filters, 'type')) {
            if ($filters['type'] == 'labor') {
                $query->whereGroupId(User::GROUP_LABOR);
            } elseif ($filters['type'] == 'sub_contractor') {
                $query->onlySubContractors();
            }
        }

        // by email and full name
        if (ine($filters, 'query')) {
            $query->where(function ($query) use ($filters) {
                $query->where('email', 'Like', '%' . $filters['query'] . '%');
                $query->orWhereRaw("CONCAT(first_name,' ',last_name) LIKE ?", ['%' . $filters['query'] . '%']);
            });
        }

        if(ine($filters, 'only_deactivated')) {
			$query->whereActive(false);
		}

        return $query;
    }

    private function getIncludes($input)
    {
        $with = ['profile', 'laborTrades', 'profile.country', 'profile.state', 'laborWorkTypes'];

        if (!isset($input['includes'])) {
            return $with;
        }

        if (in_array('rate_sheet', $input['includes'])) {
            $with[] = 'financialDetails';
        }

        if(in_array('role', $input['includes'])){
            $with[] = 'role';
        }

        if(in_array('divisions', $input['includes'])){
			$with[] = 'divisions';
		}

        return $with;
    }

    /**
     * Get trashed sub contractor prime
     * @param int $id
     * @return Illuminate\Database\Eloquent\Model
     */
	public function getTrashedSubContractorPrime($id)
	{
		return $this->make()->onlyTrashed()
			->subContractorPrime()
			->findOrFail($id);
	}
}
