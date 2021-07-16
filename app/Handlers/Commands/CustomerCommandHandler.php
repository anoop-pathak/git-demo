<?php namespace App\Handlers\Commands;

use App\Events\CustomerRepAssigned;
use App\Models\Customer;
use App\Models\TempImportCustomer;
use App\Repositories\CustomerRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

class CustomerCommandHandler
{

    protected $command;
    protected $repo;

    public function __construct(CustomerRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Handle the command.
     *
     * @param object $command
     * @return void
     */
    public function handle($command)
    {
        $this->command = $command;
        if (!$command->stopDBTransaction) {
            DB::beginTransaction();
        }

        try {
            $customer = $this->repo->saveCustomer(
                $command->customerData,
                $command->addressData,
                $command->phonesData,
                $command->isBillingAddressSame,
                $command->billingAddressData,
                $command->geocodingRequired,
                $command->flags,
                $command->customerContacts,
                $command->customFields
            );

            $this->assignRep($customer, $command->rep);
            $this->assignCanvasserAndCallCenterRep($customer,$command->customerData);

            // delete temporally stored record if exists..
            if ($command->tempId) {
                TempImportCustomer::whereId($command->tempId)->delete();
            }
        } catch (\Exception $e) {
            if (!$command->stopDBTransaction) {
                DB::rollback();
            }
            throw $e;
        }

        if (!$command->stopDBTransaction) {
            DB::commit();
        }

        //Event..
        return $customer;
    }

    private function assignRep($customer, $rep)
    {
        $oldRep = $customer->rep_id;

        // set representative..
        Customer::where('id', $customer->id)->update(['rep_id' => $rep]);
        $stopPushNotification = config('stop_push_notifiction');

        if(!$stopPushNotification){
            $assignedBy = Auth::user();
            Event::fire('JobProgress.Customers.Events.CustomerRepAssigned', new CustomerRepAssigned($customer, $assignedBy, $rep, $oldRep));
        }
    }

    private function assignCanvasserAndCallCenterRep($customer, $customerData)
    {
        if(ine($customerData, 'call_center_rep_id')) {
            Customer::where('id', $customer->id)->update([
                'call_center_rep_id' => $customerData['call_center_rep_id']
            ]);
        }

        if(ine($customerData, 'canvasser_id')) {
            Customer::where('id', $customer->id)->update(['canvasser_id' => $customerData['canvasser_id']]);
        }
    }
}
