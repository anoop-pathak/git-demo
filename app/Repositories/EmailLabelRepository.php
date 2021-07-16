<?php

namespace App\Repositories;

use App\Models\EmailLabel;
use App\Services\Contexts\Context;
use Illuminate\Support\Facades\Auth;

class EmailLabelRepository extends ScopedRepository
{
    protected $model;
    protected $scope;

    function __construct(EmailLabel $model, Context $scope)
    {
        $this->model = $model;
        $this->scope = $scope;
    }


    public function saveLabels($input)
    {
        $emailLabel = $this->model;

        $emailLabel->company_id = $this->scope->id();
        $emailLabel->name = $input['name'];
        $emailLabel->created_by = \Auth::user()->id;

        $emailLabel->save();

        return $emailLabel;
    }

    public function getFilteredLabels($filters = [], $sortable = true)
    {
        if ($sortable) {
            $labels = $this->make()->Sortable();
        } else {
            $labels = $this->make();
        }

        $this->applyFilters($labels, $filters);

        return $labels;
    }

    /************* Private Section **************/

    private function applyFilters($query, $filters)
    {
        if (ine($filters, 'user_id') && (\Auth::user()->isAdmin())) {
            $query->where('email_labels.created_by', $filters['user_id']);
        } else {
            $query->where('email_labels.created_by', \Auth::id());
        }
    }
}
