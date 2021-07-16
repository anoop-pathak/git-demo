<?php

namespace App\Repositories;

use App\Models\CompnayOnboardChecklist;
use App\Models\OnboardChecklist;
use App\Models\OnboardChecklistSection;
use App\Services\Contexts\Context;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class OnboardChecklistRepository extends ScopedRepository
{

    /**
     * The base eloquent OnboardChecklist
     * @var Eloquent
     */
    protected $model;


    protected $scope;

    function __construct(OnboardChecklist $model, Context $scope)
    {
        $this->model = $model;
        $this->scope = $scope;
    }

    /**
     * Save Onboard checklist
     * @param  array $data Array Data
     * @return Onboard checklist object
     */
    public function save($data)
    {
        return OnboardChecklist::create($data);
    }

    /**
     * get filtererd checklist
     * @param  array $filters Filters
     * @param  boolean $sortable Sortable
     * @return Query builder
     */
    public function getFilteredCheckList($filters = [], $sortable = true)
    {
        if ($sortable) {
            $onboard = $this->model->sortable();
        } else {
            $onboard = $this->model;
        }

        if (ine($filters, 'includes') && in_array('section', $filters['includes'])) {
            $onboard->with('section');
        }

        return $onboard;
    }

    /**
     * Save Company Check list
     * @param  Int $checklistId Check list id
     * @param  Boolean $selected Selected Value
     * @return Boolean
     */
    public function saveCompanyChecklist($checklistId, $selected)
    {

        if (!$this->scope->has()) {
            throw new \Exception("Company scope not set.");
        }

        if ($selected) {
            CompnayOnboardChecklist::create([
                'company_id' => $this->scope->id(),
                'checklist_id' => $checklistId,
            ]);
        } else {
            CompnayOnboardChecklist::whereCompanyId($this->scope->id())
                ->whereChecklistId($checklistId)
                ->delete();
        }

        return true;
    }

    /**
     * Get Company Check list
     * @return Array
     */
    public function getCompanyChecklist()
    {
        if (!$this->scope->has()) {
            throw new \Exception("Company scope not set.");
        }
        $companyId = $this->scope->id();

        $checklist = OnboardChecklistSection::leftJoin(
            'onboard_checklists as oc',
            'onboard_checklist_sections.id',
            '=',
            'oc.section_id'
        )
            ->select(
                'onboard_checklist_sections.id',
                'onboard_checklist_sections.title as section',
                'onboard_checklist_sections.position as position',
                'oc.id',
                'oc.section_id',
                'oc.title',
                'oc.action',
                'oc.video_url',
                'oc.is_required',
                DB::raw(
                    '(CASE WHEN EXISTS 
						(select * from company_onboard_checklist coc where oc.id =  coc.checklist_id and company_id =' . $companyId . '
						) 
						THEN TRUE ELSE FALSE 
					END
					) as selected'
                )
            )
            ->whereNotNull('oc.id')
            ->orderBy('position', 'asc')
            ->get()
            ->groupBy('section');

        //get company selected list count

        $sectionRepo = App::make(\App\Repositories\OnboardChecklistSectionRepository::class);
        $totalSection = $sectionRepo->count();
        $totalCompletedSection = $totalSection - $sectionRepo->getUncompletedSectionCount();

        $data['data'] = $checklist;

        $data['meta'] = [
            'total_section_count' => $totalSection,
            'completed_section_count' => $totalCompletedSection,
        ];

        return $data;
    }
}
