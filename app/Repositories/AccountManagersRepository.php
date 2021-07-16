<?php namespace App\Repositories;

use App\Models\AccountManager;

class AccountManagersRepository extends AbstractRepository
{

    /**
     * The base eloquent model
     * @var Eloquent
     */
    protected $model;

    function __construct(AccountManager $model)
    {

        $this->model = $model;
    }

    public function getFilteredAccountManagers($filters, $sortable = true)
    {
        $accountManagers = $this->getAccountManagers($sortable);
        $this->applyFilters($accountManagers, $filters);
        return $accountManagers;
    }

    public function getAccountManagers($sortable = true)
    {
        $accountManagers = null;

        if ($sortable) {
            $accountManagers = $this->model->Sortable()
                ->leftJoin('states', 'account_managers.state_id', '=', 'states.id')
                ->select('account_managers.*');
        } else {
            $accountManagers = $this->model->Sortable();
        }

        $accountManagers->with('state', 'trades');

        return $accountManagers;
    }

    public function getList()
    {
        $list = $this->model->select('id', 'first_name', 'last_name', 'uuid')->get();

        return $list;
    }

    // get account manager by uuid..
    public function getByUUID($uuid)
    {
        $accountManager = $this->model->where('uuid', $uuid)->firstOrFail();
        return $accountManager;
    }

    //get account manager by filed
    public function getByField($attribute, $value)
    {
        $accountManager = $this->model->where($attribute, $value)
            ->firstOrFail();
        return $accountManager;
    }


    /***************** Private function *****************/

    private function applyFilters($query, $filters)
    {
        if (ine($filters, 'trades')) {
            $query->where(function ($query) use ($filters) {
                $query->whereIn('account_managers.id', function ($query) use ($filters) {
                    $query->select('account_manager_id')->from('account_manager_trade')->whereIn('trade_id', (array)$filters['trades']);
                })->orWhere('for_all_trades', true);
            });
        }
    }
}
