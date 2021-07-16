<?php
namespace App\Services\AmericanFoundation\Commands;

use Illuminate\Console\Command;
use App\Services\AmericanFoundation\Models\AfReferral;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class MoveAfReferralsToReferralsTableCommand extends Command
{

    /**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:af_referrals_move_to_company_referrals';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
    protected $description = 'Move American Foundation referrals from af_referrals table to company referrals table.';

    private $inc = 0;

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
        AfReferral::chunk(50, function($items){

                foreach ($items as $item) {

                    if($item->referral_id) {
                        continue;
                    }

                    try {
                        $arrData = $this->fieldsMapping($item);

                        setScopeId($item->company_id);

                        $repository = App::make('App\Repositories\ReferralRepository');
                        $savedUser = $repository->saveReferral($arrData);
                        $item->referral_id = $savedUser->id;
                        $item->save();
                        $this->inc++;
                        if($this->inc %100 == 0) {
                            $this->info("Total Processing users:- " . $this->inc);
                        }

                    } catch (\Exception $e) {
                        Log::error("Error in American Foundation Move AfReferrals to referrals table");
                        Log::error($e);
                    }
                }
        });
        $this->info("Total Processed users:- " . $this->inc);
    }

    private function fieldsMapping(AfReferral $item)
    {

        return array(
            // "company_id" => $item->company_id,
            "name" => $item->name,
        );
    }
}