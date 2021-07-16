<?php namespace App\QuickBooks\QueueHandler;

use App\Exceptions\QuickBookException;
use App\Models\Customer;
use App\Models\Job;
use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class QuickBookQueueHandler
{
    /**
     * Sync Job
     * @param $job     Object (Queue)
     * @param $jobData Job Data
     */
    public function syncJob($job, $jobData)
    {
        try {
            if ($this->setCompanyScope($jobData)) {
                $jobRepo = App::make(\App\Repositories\JobRepository::class);
                $data = $jobRepo->getById($jobData['id']);

                $qbService = App::make(\App\Services\QuickBooks\QuickBookService::class);
                $token = $qbService->getToken();
                if ($token) {
                    $qbService->getJobQuickbookId($token, $data);
                }
            }
        } catch (AuthorizationException $e) {
            //error handle
        } catch (QuickBookException $e) {
            //error handle
        } catch (\Exception $e) {
            //error Handle
        }

        $job->delete();
    }

    /**
     * Sync Customer
     * @param $job      Object (Queue)
     * @param $customer Customer Data
     */
    public function syncCustomer($job, $customer)
    {
        try {
            if ($this->setCompanyScope($customer)) {
                $customerRepo = App::make(\App\Repositories\CustomerRepository::class);
                $customer = $customerRepo->getById($customer['id']);

                $qbService = App::make(\App\Services\QuickBooks\QuickBookService::class);
                $token = $qbService->getToken();
                if ($token) {
                    $qbService->createOrUpdateCustomer($token, $customer);
                }
            }
        } catch (AuthorizationException $e) {
            //error handle
        } catch (QuickBookException $e) {
            //error handle
        } catch (\Exception $e) {
            //error Handle
        }

        $job->delete();
    }

    /**
     * Set Company Scope
     */
    private function setCompanyScope($data)
    {
        $user = User::find(\Crypt::decrypt($data['current_user_id']));

        if (!$user) {
            return false;
        }

        \Auth::guard('web')->login($user);

        setScopeId($user->company_id);

        return true;
    }
}
