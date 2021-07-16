<?php
namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Proposal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AddSerialNumberOfProposalsCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:add_serial_number_of_proposals';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'add serial_number for old proposals';

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
    public function handle()
    {
        $companyIds = Company::pluck('id')->toArray();

        foreach ($companyIds as $compnayId) {
            $serialNumber = 1;

            $proposals = Proposal::whereCompanyId($compnayId)
                ->select('id')
                ->chunk(100, function ($proposals) use ($serialNumber) {
                    foreach ($proposals as $key => $proposal) {
                        DB::table('proposals')->whereId($proposal->id)
                            ->update(['serial_number' => $serialNumber]);

                        $serialNumber++;
                    }
                });
        }
    }
}
