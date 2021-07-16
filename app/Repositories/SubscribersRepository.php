<?php namespace App\Repositories;

use App\Models\Company;
use App\Models\CompanyNetwork;
use App\Models\Supplier;
use App\Models\User;
use DB;
use App\Models\SubscriberStage;

class SubscribersRepository extends AbstractRepository
{

    /**
     * The base eloquent model
     * @var Eloquent
     */
    protected $model;

    /**
     * The current sort field and direction
     * @var array
     * @TODO make use of default sorting parameters
     */
    protected $currentSort = ['created_at', 'desc'];

    function __construct(Company $model)
    {

        $this->model = $model;
    }

    public function getFilteredSubscribers($filters, $sortable = true)
    {

        $subscribers = $this->getSubscribers($sortable, $filters);

        $this->applyFilters($subscribers, $filters);

        return $subscribers;
    }

    public function getSubscribers($sortable = true, $filters)
    {
        $subscribers = null;

        if ($sortable) {
            $subscribers = $this->model->Sortable()
                ->orderBy('id', 'desc')
                ->leftJoin('users', 'users.company_id', '=', 'companies.id')->where('group_id', User::GROUP_OWNER)
                ->leftJoin('account_managers', 'account_managers.id', '=', 'companies.account_manager_id')
                ->leftJoin('subscriptions', 'companies.id', '=', 'subscriptions.company_id')
                ->select('companies.*');
        } else {
            $subscribers = $this->model->Sortable()->orderBy('id', 'desc');
        }

        $with = [
			'accountManager',
			'state',
			'country',
			'subscription.plan',
			'users'
		];

		if (ine($filters, 'includes')) {
			$includes = (array)$filters['includes'];
			if (in_array('subscription', $includes)) {
				$with[] = 'subscription';
			}
			if (in_array('trades', $includes)) {
				$with[] = 'trades';
			}
			if (in_array('stage_attribute', $includes)) {
				$with[] = 'subscriberLatestStageAttribute';
			}
			if (in_array('license_numbers', $includes)) {
				$with[] = 'licenseNumbers';
			}
			if (in_array('subs', $includes)) {
				$with[] = 'subscriber';
			}
		}

		$subscribers->with($with);

        $subscribers->leftJoin('ev_clients', function($join){
                $join->on('companies.id', '=', 'ev_clients.company_id')
                     ->WhereNull('ev_clients.deleted_at');
            })
            ->leftJoin('google_clients', function($join){
                $join->on('companies.id', '=', 'google_clients.company_id')
                     ->WhereNull('google_clients.deleted_at');
            })
            ->leftJoin('quickbooks', 'companies.id', '=', 'quickbooks.company_id')
            ->leftJoin('hover_clients', function($join){
                $join->on('companies.id', '=', 'hover_clients.company_id')
                     ->WhereNull('hover_clients.deleted_at');
            })
            ->leftJoin('sm_clients', function($join){
                $join->on('companies.id', '=', 'sm_clients.company_id')
                     ->WhereNull('sm_clients.deleted_at');
            })
            ->leftJoin('company_cam_clients', 'companies.id', '=', 'company_cam_clients.company_id')
            ->leftJoin('company_networks as facebook', function($join){
                $join->on('companies.id', '=', 'facebook.company_id')
                     ->where('facebook.network', '=', CompanyNetwork::FACEBOOK);
            })
            ->leftJoin('company_networks as twitter', function($join){
                $join->on('companies.id', '=', 'twitter.company_id')
                     ->where('twitter.network', '=', CompanyNetwork::TWITTER);
            })
            ->leftJoin('company_networks as linkedin', function($join){
                $join->on('companies.id', '=', 'linkedin.company_id')
                     ->where('linkedin.network', '=', CompanyNetwork::LINKEDIN);
            })
            ->leftJoin('quickbooks_user', function($join){
                $join->on('companies.id', '=', 'quickbooks_user.company_id')
                     ->where('quickbooks_user.setup_completed', '=', true);
            })
            ->leftJoin('company_supplier as abc_supplier', function($join){
                $join->on('companies.id', '=', 'abc_supplier.company_id')
                     ->where('abc_supplier.supplier_id', '=', Supplier::ABC_SUPPLIER_ID)
                     ->WhereNull('abc_supplier.deleted_at');
            })
            ->leftJoin('company_supplier as srs_supplier', function($join){
                $join->on('companies.id', '=', 'srs_supplier.company_id')
                     ->where('srs_supplier.supplier_id', '=', Supplier::srs()->id)
                     ->WhereNull('srs_supplier.deleted_at');
            })
            ->leftJoin('quickbooks as quichbook_pay', function($join){
                $join->on('companies.id', '=', 'quichbook_pay.company_id')
                     ->where('quichbook_pay.is_payments_connected', '=', true);
            })
            ->addSelect(DB::raw('google_clients.company_id as google_sheet, ev_clients.company_id as eagleview, quickbooks.company_id as quickbook, hover_clients.company_id as hover, sm_clients.company_id as skymeasure, company_cam_clients.company_id as company_cam, facebook.company_id as facebook, twitter.company_id as twitter, linkedin.company_id as linkedin, quickbooks_user.company_id as quickbook_desktop, abc_supplier.company_id as abc_supplier, srs_supplier.company_id as srs_supplier, quichbook_pay.company_id as quickbookpay'));

        return $subscribers;
    }

    public function saveSubscriberStage( $companyId, $stageAttributeId)
	{
		$subscriberOldStage = SubscriberStage::where('company_id', $companyId)->delete();

		$subscriberStage = new SubscriberStage;
		$subscriberStage->subscriber_stage_attribute_id = $stageAttributeId;
		$subscriberStage->company_id = $companyId;
		$subscriberStage->save();

		return $subscriberStage;
	}


    /** Private Functions **/

    private function applyFilters($query, $filters)
    {

        if (ine($filters, 'keyword')) {
            $query->where(function ($query) use ($filters) {
                $query->whereIn('companies.id', function ($query) use ($filters) {
                    $query->select('company_id')
                        ->from('users')
                        ->whereRaw("CONCAT(first_name,' ',last_name) LIKE ?", ['%' . $filters['keyword'] . '%']);
                })->orWhere('companies.name', 'Like', '%' . $filters['keyword'] . '%');
            });
        }


        if (ine($filters, 'company_name')) {
            $query->name($filters['company_name']);
        }

        if(ine($filters, 'phone')) {
			$query->where('companies.office_phone', 'Like', $filters['phone'].'%');
		}

        if (ine($filters, 'account_manager_id')) {
            $query->accountManager($filters['account_manager_id']);
        }

        if (ine($filters, 'state')) {
            $query->state($filters['state']);
        }

        if (ine($filters, 'country')) {
            $query->where('office_country', $filters['country']);
        }

        if (ine($filters, 'company_id')) {
            $query->where('companies.id', $filters['company_id']);
        }

        if (ine($filters, 'email')) {
            $query->whereIn('companies.id', function ($query) use ($filters) {
                $query->select('company_id')
                    ->from('users')
                    ->where('email', 'Like', '%' . $filters['email'] . '%')
                    ->where('group_id', User::GROUP_OWNER);
            });
        }

        if (ine($filters, 'admin')) {
            $query->whereIn('companies.id', function ($query) use ($filters) {
                $query->select('company_id')
                    ->from('users')
                    ->whereRaw("CONCAT(first_name,' ',last_name) LIKE ?", ['%' . $filters['admin'] . '%'])
                    ->where('group_id', User::GROUP_OWNER);
            });
        }

        if (ine($filters, 'activated')) {
            $query->activated($filters['activated']);
        }

        if (ine($filters, 'activation_date') || ine($filters, 'activation_date_end')) {
            $start = ine($filters, 'activation_date') ? $filters['activation_date'] : null;
            $end = ine($filters, 'activation_date_end') ? $filters['activation_date_end'] : null;
            $query->activationDateRange($start, $end);
        }

        # status updated at filter
        if(ine($filters, 'start_date') || ine($filters, 'end_date')){
            $start = ine($filters, 'start_date') ? $filters['start_date'] : null;
            $end   = ine($filters, 'end_date') ? $filters['end_date'] : null;
            $query->statusUpdatedAt($start, $end);
        }

        //subscription status
        if (ine($filters, 'status')) {
            $query->whereIn('subscriptions.status', (array)$filters['status']);
            // $query->activated($filters['status']);
        }

        //subscription plan
        if (ine($filters, 'product_id')) {
            $query->where('subscriptions.product_id', $filters['product_id']);
        }

        if (ine($filters, 'trashed')) {
            $query->onlyTrashed();
        }

        if(ine($filters, 'trades')) {
			$query->join('company_trade', 'companies.id', '=', 'company_trade.company_id')
            	->join('trades', 'company_trade.trade_id', '=', 'trades.id')
            	->whereIn('trades.id', $filters['trades']);
        }

		if(ine($filters,'stage_attributes_ids')) {
			$query->whereIn('companies.id', function($query) use($filters){
				$query->select('company_id')
					->from('subscriber_stages')
					->whereIn('subscriber_stage_attribute_id', (array)$filters['stage_attributes_ids'])
					->whereNull('deleted_at');
			});
		}
    }
}
