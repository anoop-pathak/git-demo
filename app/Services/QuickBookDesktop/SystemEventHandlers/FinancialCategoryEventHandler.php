<?php
namespace App\Services\QuickBookDesktop\SystemEventHandlers;

use App\Services\Repositories\FinancialAccountRepository;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Events\FinancialAccountCreated;
use App\Services\QuickBookDesktop\Setting\Settings;
use App\Services\QuickBookDesktop\Entity\Account as QBDAccount;
use QBDesktopQueue;
use Illuminate\Support\Facades\Event;

class FinancialCategoryEventHandler
{

    public function __construct()
    {
        $this->financialAccountRepo = App::make(FinancialAccountRepository::class);

        $this->settings = App::make(Settings::class);
        $this->qbdAccount = App::make(QBDAccount::class);
    }

    public function subscribe($event)
    {
        $event->listen('JobProgress.Events.FinancialCategoryCreated',
            'App\Services\QuickBookDesktop\SystemEventHandlers\FinancialCategoryEventHandler@create');
	}

	public function create($event)
	{

        try {

            $category = $event->meta['category'];

            $username = QBDesktopQueue::getUsername($category->company_id);

            if (!$username) {
                return false;
            }

            $account = $this->qbdAccount->getORCreateAccount($category->name);

            // If account exists and synced then map with category
            if ($account) {
                $category->financial_account_id = $account->id;
                $category->save();
            }

            if ($account->qb_desktop_id) {
                return true;
            }

            Event::fire('JobProgress.Events.FinancialAccountCreated', new FinancialAccountCreated($account));

        } catch(Exception $e) {
            Log::info($e->getMessage());
        }
	}
}
