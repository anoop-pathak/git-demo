<?php

namespace App\Repositories;

use App\Models\OnboardChecklistSection;
use App\Services\Contexts\Context;
use Illuminate\Support\Facades\DB;

class OnboardChecklistSectionRepository extends ScopedRepository
{

    /**
     * The base eloquent JobSchedule
     * @var Eloquent
     */
    protected $model;

    protected $scope;

    public function __construct(OnboardChecklistSection $model, Context $scope)
    {
        $this->model = $model;
        $this->scope = $scope;
    }

    /**
     * Save section
     * @param  Array $data section data
     * @return OnboardChecklistObject Object
     */
    public function save($data)
    {

        return OnboardChecklistSection::create($data);
    }

    /**
     * Get Filter Onboard Section
     * @param  array $filters Filters
     * @param  boolean $sortable Sortable value
     * @return QueryBuilder
     */
    public function getFilteredOnBoardSection($filters = [], $sortable = true)
    {
        if ($sortable) {
            $onboard = $this->model->sortable();
        } else {
            $onboard = $this->model;
        }

        if (ine($filters, 'includes') && in_array('checklists', $filters['includes'])) {
            $onboard->with('checklists');
        }

        return $onboard;
    }

    /**
     * Update Position
     * @param  Int $id Section id
     * @param  Int $Position Position order
     * @return Void
     */
    public function updatePosition($id, $position)
    {
        $section = $this->model->find($id);
        if (!$section) {
            return false;
        }

        $section->update(['position' => $position]);

        return true;
    }

    /**
     * Get Uncompleted Section Count
     * @return Int Count
     */
    public function getUncompletedSectionCount()
    {
        $companyId = $this->scope->id();

        $count = OnboardChecklistSection::leftJoin(
            'onboard_checklists as oc',
            'onboard_checklist_sections.id',
            '=',
            'oc.section_id'
        )->leftJoin(
            DB::raw('(select * from company_onboard_checklist where company_id =' . $companyId . ') as coc'),
            'oc.id',
            '=',
            'coc.checklist_id'
        )
            ->whereNotNull('oc.id')
            ->whereNull('coc.checklist_id')
            ->groupBy('section_id')
            ->get()
            ->count();

        return $count;
    }

    /**
     * Get Section count
     * @return Int Count
     */
    public function count()
    {
        return $this->model->has('checklists')->count();
    }
}
