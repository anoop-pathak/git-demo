<?php
namespace App\Services\Grid;
use Auth;
use App\Exceptions\InvalidDivisionException;
use Request;
use App\Models\FinancialCategory;
use App\Models\User;


trait DivisionTrait {
    public function scopeDivision($query) {
        if(Request::get('disable_division')) return $query;
        if(!Auth::user()) return $query;

        if(((Auth::user()->isOwner() || Auth::user()->isAnonymous())  && !Request::has('division_ids') && !Request::has('unassigned_division'))
            || Auth::user()->isSuperAdmin()
            || (Auth::user()->all_divisions_access && !Request::has('division_ids')) && !Request::has('unassigned_division')) return $query;

        $divisionIds = [];
        $includeWithoutDivisionData = true;

        if(Request::has('division_ids') && Request::has('unassigned_division')) {
            $divisionIds =  \Request::get('division_ids');
        } elseif(Request::has('division_ids') && !Request::has('unassigned_division')) {
            $divisionIds =  Request::get('division_ids');
            $includeWithoutDivisionData = false;
        } elseif(!Request::has('division_ids') && !Request::has('unassigned_division')) {
            $divisionIds = Auth::user()->divisions->pluck('id')->toArray();
        }

        if(in_array(0, $divisionIds)) {
            $divisionIds = array_filter($divisionIds);
            $includeWithoutDivisionData = true;
        }

        if(!empty($divisionIds)
            && !Auth::user()->all_divisions_access
            && !Auth::user()->isOwner()
            && !(bool)array_intersect(Auth::user()->divisions->pluck('id')->toArray(), $divisionIds)){
            throw new InvalidDivisionException(trans('response.error.invalid', ['attribute' => 'division id(s)']));
        }
        switch (get_called_class()) {
            case 'App\Models\User':
                $query->where(function($query) use($divisionIds) {
                    $query->whereIn('users.id', function ($query) use ($divisionIds) {
                        $query->select("user_id")
                            ->from('user_division')
                            ->whereIn('division_id', (array)$divisionIds);
                    })->orWhere('users.all_divisions_access', true);
                });
                break;
            case 'App\Models\Job':
                $query->where(function($query) use($divisionIds, $includeWithoutDivisionData) {
                    $query->whereIn('jobs.division_id', (array) $divisionIds);
                    if($includeWithoutDivisionData) {
                        $query->orWhere('jobs.division_id', '=', 0);
                    }
                });
                break;
            case 'App\Models\ActivityLog':
                $query->where(function($query) use($divisionIds, $includeWithoutDivisionData) {
                    $query->whereIn('job_id', function($query)use($divisionIds){
                            $query->select('id')
                                ->from('jobs')
                                ->whereIn('jobs.division_id', (array)$divisionIds);
                        });
                    if($includeWithoutDivisionData) {
                        $query->orWhereIn('job_id', function($query){
                            $query->select('id')
                                ->from('jobs')
                                ->where('jobs.division_id', '=', 0);
                        });
                    }
                });
                break;
            case 'App\Models\Template':
                $query->where(function($query) use($divisionIds) {
                     $query->whereIn('templates.id', function($query) use($divisionIds){
                        $query->select('template_id')
                            ->from('template_division')
                            ->whereIn('division_id', $divisionIds);
                    })->orWhere('templates.all_divisions_access', true);
                });
                break;
            case 'App\Models\FinancialMacro':
                $query->where(function($query) use($divisionIds) {
                     $query->whereIn('financial_macros.id', function($query) use($divisionIds){
                        $query->select('macro_id')
                            ->from('macro_division')
                            ->whereIn('macro_division.division_id', $divisionIds);
                    })->orWhere('financial_macros.all_divisions_access', true);
                });
                break;
            case 'App\Models\MessageThread':
                if(!empty($divisionIds)) {
                    $query->where(function($query) use($divisionIds) {
                        $query->whereIn('message_threads.id', function($query) use($divisionIds) {
                            $query->select('thread_id')->from('message_thread_participants')
                                ->join('user_division', 'user_division.user_id', '=', 'message_thread_participants.user_id')
                                ->whereIn('user_division.division_id', (array)$divisionIds);
                        })->orWherein('message_threads.id', function($query){
                            $query->select('thread_id')->from('message_thread_participants')
                                ->join('users', 'users.id', '=', 'message_thread_participants.user_id')
                                ->where('users.company_id', getScopeId())
                                ->where('users.all_divisions_access', true);
                        });
                    });
                }
                break;
            case 'App\Models\Task':
                if(!empty($divisionIds)) {
                    $query->where(function($query) use($divisionIds) {
                        $query->whereIn('tasks.id', function($query) use($divisionIds) {
                            $query->select('task_id')->from('task_participants')
                                ->join('user_division', 'user_division.user_id', '=', 'task_participants.user_id')
                                ->whereIn('user_division.division_id', (array)$divisionIds)
                                ->groupBy('user_division.user_id');
                        })->orWhereIn('tasks.id', function($query){
                            $query->select('task_id')->from('task_participants')
                                ->join('users', 'users.id', '=', 'task_participants.user_id')
                                ->where('users.company_id', getScopeId())
                                ->where('users.all_divisions_access', true);
                        });
                    });
                }
                break;
            case 'App\Models\FinancialProduct':
                $categoryIds = (array)Request::get('categories_ids');
                $financialCategory = null;
                if(!empty($categoryIds)) {
                    $financialCategory = FinancialCategory::where('name', FinancialCategory::LABOR)
                            ->where('company_id', getScopeId())
                            ->first();
                    if(!($financialCategory && in_array($financialCategory->id, $categoryIds)) ) {
                        $divisionIds = [];
                    }
                } else {
                    $divisionIds = [];
                }
                if(!empty($divisionIds)) {
                    $query->where(function($query) use($divisionIds, $financialCategory, $categoryIds){
                        $query->whereIn('financial_products.labor_id', function($query) use($divisionIds) {
                            $query->select('user_id')->from('user_division')
                                ->whereIn('user_division.division_id', (array)$divisionIds);
                        })->orWhereIn('financial_products.labor_id', function($query){
                            $query->select('id')->from('users')
                                ->whereNull('users.deleted_at')
                                ->where('users.company_id', getScopeId())
                                ->whereIn('users.group_id', [User::GROUP_SUB_CONTRACTOR, User::GROUP_SUB_CONTRACTOR_PRIME])
                                ->where('users.all_divisions_access', true);
                        });
                        if($financialCategory && in_array($financialCategory->id, $categoryIds)) {
                            $query->orWhere(function($query) {
                                $query->whereNull('labor_id')
                                    ->orWhere('labor_id', 0);
                            });
                        }
                    });
                }
                break;
        }
    }
}