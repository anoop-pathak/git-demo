<?php namespace App\Repositories;

use App\Models\CompanyContact;
use App\Services\Contexts\Context;

class CompanyContactsRepository extends ScopedRepository
{

    protected $model;
    protected $scope;

    function __construct(CompanyContact $model, Context $scope)
    {
        $this->model = $model;
        $this->scope = $scope;
    }

    public function save($data)
    {
        $tagIds = isset($data['tag_ids']) ? $data['tag_ids'] : null;
        $data['company_id'] = $this->scope->id();

        //edit case..
        if (isset($data['id']) && !empty($data['id'])) {
            $companyContact = $this->model->find($data['id']);
            return $companyContact->update($data);
        }

        return $this->model->create($data);
    }

    public function getFilteredCompanyContacts($filters, $sortable = true)
    {

        $companyContacts = $this->getCompanyContacts($sortable);
        $this->applyFilters($companyContacts, $filters);
        return $companyContacts;
    }

    public function getCompanyContacts($sortable = true)
    {
        $companyContact = null;

        if ($sortable) {
            $companyContact = $this->make()
                ->sortable()
                ->orderBy('id', 'desc');
        } else {
            $companyContact = $this->make()
                ->orderBy('id', 'desc');
        }

        return $companyContact;
    }

    /** Private Functions **/

    private function applyFilters($query, $filters)
    {

        if (ine($filters, 'keyword')) {
            $query->whereRaw("CONCAT(first_name,' ',last_name) LIKE ?", ['%' . $filters['keyword'] . '%']);
        }

        // search filter by company_name
		if(ine($filters, 'company_name')){
			$query->whereRaw("company_name LIKE ?",['%'.$filters['company_name'].'%']);
		}

        // by email and full name
        if (ine($filters, 'query')) {
            $query->where(function ($query) use ($filters) {
                $query->where('email', 'Like', '%' . $filters['query'] . '%');
                $query->orWhereRaw("CONCAT(first_name,' ',last_name) LIKE ?", ['%' . $filters['query'] . '%']);
            });
        }
    }
}
