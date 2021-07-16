<?php

namespace App\Repositories;

use App\Models\Snippet;
use App\Services\Contexts\Context;
use Illuminate\Support\Facades\Auth;

class SnippetRepository extends ScopedRepository
{
    protected $scope;
    protected $model;

    function __construct(Snippet $model, Context $scope)
    {
        $this->scope = $scope;
        $this->model = $model;
    }

    public function create($data)
    {
        $snippet = new Snippet($data);
        $snippet->company_id = $this->scope->id();
        $snippet->created_by = \Auth::id();
        $snippet->save();

        return $snippet;
    }

    public function getSnippets($filters = [])
    {
        $snippets = $this->make();

        $this->applyFilters($snippets, $filters);

        return $snippets;
    }

    private function applyFilters($query, $filters = [])
    {
        //title snippet filter
        if (ine($filters, 'title')) {
            $query->where('title', 'like', '%' . $filters['title'] . '%');
        }
    }
}
