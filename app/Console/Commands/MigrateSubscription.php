<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\ApiResponse;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Email;
use App\Models\Job;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Product;
use App\Models\Task;
use App\Models\WorkflowStage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class MigrateSubscription extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:migrate-subscription';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate Subscription Plan.';


    protected $workflowRepo;
    protected $userRepo;

    /**
     * Create a new command instance.
     *
     * @return void
     */

    public function __construct()
    {
        $this->workflowRepo = App::make(\App\Repositories\WorkflowRepository::class);
        $this->userRepo = App::make(\App\Repositories\UserRepository::class);
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $subscriptionId = $this->ask('Enter Subscription Id :');
        $company = Company::find($subscriptionId);
        if (!$company) {
            $this->error('Invalid Subscription Id.');
            return;
        }
        $currentSubscription = $company->subscription;
        $currentProductId = $currentSubscription->product_id;

        $products = Product::where('id', '!=', $currentProductId)
            ->whereIn('id', [2, 4, 7, 8, 9, 10, 11]);// only plus plan..
        $productsList = $products->pluck('title', 'id')->toArray();
        $productsIds = $products->pluck('id')->toArray();

        // show current plan
        echo "\nCurrent Plan : " . $currentSubscription->product->title . "\n\n";

        // show other plans list
        foreach ($productsList as $key => $name) {
            echo "$key : $name \n";
        }

        //prompt to choose new plan
        choosePlan :
        $newProductId = $this->ask('Select new plan :');
        if (!in_array($newProductId, $productsIds)) {
            $this->error('Invalid Input.');
            goto choosePlan;
        }

        // $newProductId = 4;// plus free
        enterPassword :
        $password = $this->secret('Please Enter Password to Proceed : ');
        if ($password != config('jp.developer_secret')) {
            $this->error('Incorrect Password.');
            goto enterPassword;
        }
        DB::beginTransaction();
        try {
            //change workflow
            // $this->changeWorkflow($company, $currentProductId);
            // $this->changeRoles($company, $newProductId);
            $this->updateSubscription($currentSubscription, $newProductId);
            // $this->deleteCompanydata($company);
        } catch (\Exception $e) {
            DB::rollback();
            $this->error(ApiResponse::errorInternal('Internal Error', $e));
            return;
        }
        DB::commit();
        $this->info('Subscription plan updated successfully.');
    }

    private function changeWorkflow($company, $currentProductId)
    {
        $currentWorkflow = $this->workflowRepo->getActiveWorkflow($company->id);
        $currentWorkflow = $currentWorkflow->toArray();
        $currentStages = $currentWorkflow['stages'];

        if (($currentProductId == Product::PRODUCT_JOBPROGRESS)
            || ($currentProductId == Product::PRODUCT_JOBPROGRESS_BASIC_FREE)
        ) {
            $newStages = WorkflowStage::defaultStages();
        } else {
            $newStages = WorkflowStage::defaultStagesForBasic();
        }

        // replace first and last stages of plus workflow
        $newStages[0]['code'] = $currentStages[0]['code'];
        $newStages[0]['resource_id'] = $currentStages[0]['resource_id'];

        $lastKeyOfCurrent = sizeof($currentStages) - 1;
        $lastKeyOfNewStagesStack = sizeof($newStages) - 1;
        $newStages[$lastKeyOfNewStagesStack]['code'] = $currentStages[$lastKeyOfCurrent]['code'];
        $newStages[$lastKeyOfNewStagesStack]['resource_id'] = $currentStages[$lastKeyOfCurrent]['resource_id'];
        $currentWorkflow['stages'] = $newStages;
        $this->workflowRepo->create($currentWorkflow, $company->id);
    }

    private function changeRoles($company, $productId)
    {
        $users = $company->allUsers;
        foreach ($users as $key => $user) {
            $this->userRepo->assignRole($user, $productId);
        }
    }

    private function updateSubscription($subscription, $productId)
    {
        $company = $subscription->company;
        $applicablePlan = $company->applicableSubscriptionPlan($productId);
        $service = App::make(\App\Subscriptions\SubscriptionServices::class);

        $immediatly = $this->confirm('Do you wish to upgrade immediatly (or on renew) ? [yes|no]');
        $service->updateSubscription($subscription, null, $applicablePlan, $immediatly);
        $subscription->product_id = $productId;
        $subscription->amount = $applicablePlan->amount;
        $subscription->save();
    }

    private function deleteCompanydata($company)
    {
        if (!$this->confirm('Do you wish to clear company data ? [yes|no]')) {
            return;
        }
        Customer::whereCompanyId($company->id)->delete();
        Job::whereCompanyId($company->id)->delete();
        Task::whereCompanyId($company->id)->delete();
        Appointment::whereCompanyId($company->id)->delete();
        Email::whereCompanyId($company->id)->delete();
        ActivityLog::whereCompanyId($company->id)->delete();

        $userIds = $company->allUsers->pluck('id')->toArray();
        Notification::whereIn('id', function ($query) use ($userIds) {
            $query->select('notification_id')
                ->from('notification_recipient')
                ->whereIn('user_id', $userIds);
        })->delete();

        Message::whereCompanyId($company->id)->delete();
    }
}
