<?php
namespace App\Services\AmericanFoundation\Commands;

use Carbon\Carbon;
use App\Models\Customer;
use Illuminate\Console\Command;
use App\Services\AmericanFoundation\Models\AfCustomer;
use App\Services\AmericanFoundation\Models\AfReferral;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LinkCustomersWithReferrals extends Command
{

    /**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:af_lead_source_assign_referral_to_customer';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
    protected $description = 'Move American Foundation assign referral to the customer using lead sources.';

    private $inc = 0;

    private $updateQueries = [];

    /**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
        $customerTable = app(Customer::class)->getTable();
        $acTable = app(AfCustomer::class)->getTable();

        $this->syncAfReferralId();

        $builder = AfCustomer::whereNotNull('customer_id')
                            ->select(DB::raw("$acTable.id, $acTable.company_id, $acTable.group_id, $acTable.customer_id, $acTable.af_id, c.referred_by_type, c.referred_by"))
                            ->join("$customerTable as c", function($join) use($acTable) {
                                $join->on("$acTable.customer_id", '=', 'c.id');
                            });
        $total = $builder->count();

        $datetime = Carbon::now()->format('Y-m-d H:i:s');
        $this->info($datetime . " - Total Items are:- " . $total);
        $builder->with(['afLeadSource.afReferral'])->chunk(100, function($items) use($total, $customerTable) {
                foreach ($items as $item) {
                    $this->inc++;

                    try {

                        if(trim($item->referred_by_type) && $item->referred_by) {
                            continue;
                        }

                        $afLeadSource = $item->afLeadSource;
                        if(!$afLeadSource) {
                            continue;
                        }
                        // if lead source not find then process to next af_customer.
                        if(!$afLeadSource || !$afLeadSource->afReferral) {
                            continue;
                        }

                        $referral = $afLeadSource->afReferral;
                        $cRefType = Customer::REFERRED_BY_TYPE;
                        $refId = $referral->referral_id;
                        $customeId = $item->customer_id;
                        $this->updateQueries[] = "UPDATE $customerTable SET referred_by_type='$cRefType', referred_by=$refId WHERE id=$customeId";

                        if($this->inc %100 == 0) {
                            $this->executeQueries();
                            $datetime = Carbon::now()->format('Y-m-d H:i:s');
                            $this->info($datetime . " - Total Processing link referral with customer using lead source:- " . $this->inc . " / " . $total);
                        }
                    } catch (\Exception $e) {
                        Log::error("Error in American Foundation set customer referral id from lead source report table");
                        Log::error($e);
                    }
                }
        });
        $this->executeQueries();
        $datetime = Carbon::now()->format('Y-m-d H:i:s');
        $this->info($datetime . " - Total Processed link referral with customer using lead source:- " . $this->inc . " / " . $total);
    }

    private function fieldsMapping(AfReferral $item)
    {

        return array(
            // "company_id" => $item->company_id,
            "name" => $item->name,
        );
    }

    private function executeQueries()
    {
        if(!$this->updateQueries) {
            return true;
        }

        foreach($this->updateQueries as $q) {
            DB::statement($q);
        }
        $this->updateQueries = [];
        return true;
    }

    private function syncAfReferralId()
    {
        $items = AfReferral::all();
        foreach($items as $item) {
            if($item->af_id) {
                continue;
            }
            $options = json_decode($item->options, true);
            $item->af_id = $options['id'];
            $item->save();
        }
    }
}