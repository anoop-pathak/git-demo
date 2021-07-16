<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProposalViewer;
use App\Models\Company;
use Carbon\Carbon;

class AddDefaultProposalViewer extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:add_default_proposal_viewer';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'add default proposal viewer';

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
		$companyIds = Company::pluck('id')->toArray();
		$proposalCompanyIds = arry_fu(ProposalViewer::pluck('company_id')->toArray());
		$companyIds = array_diff($companyIds ,$proposalCompanyIds);

		$data = [];
	        $totalCompaniesCount = count($companyIds);

	        $this->info("Total Companies Count: ". $totalCompaniesCount);

	        $now = Carbon::now()->toDateTimeString();
	        foreach ($companyIds as $companyId)
	        {
	        	$viewerData = [
					[
		                'title'  => 'What To Expect',
		                'company_id' => $companyId,
		                'is_active' => true,
		                'display_order' => 1,
		                'created_at'    => $now,
                		'updated_at'    => $now
		            ],
		            [
		                'title'  => 'Warranty Options',
		                'company_id' => $companyId,
		                'is_active' => true,
		                'display_order' => 2,
		                'created_at'    => $now,
                		'updated_at'    => $now
		            ],
		            [
		                'title'  => 'Financing Options' ,
		                'company_id' => $companyId,
		                'is_active' => true,
		                'display_order' => 3,
		                'created_at'    => $now,
                		'updated_at'    => $now
		            ],
		            [
		                'title'  => 'Testimonials' ,
		                'company_id' => $companyId,
		                'is_active' => true,
		                'display_order' => 4,
		                'created_at'    => $now,
                		'updated_at'    => $now
		            ],
				];

				$data = array_merge($data, $viewerData);

				--$totalCompaniesCount;

				$this->info("Pending Companies Counts: ". $totalCompaniesCount);
	        }

		if($data) {
			ProposalViewer::insert($data);
		}

		$this->info("Command End at: ".Carbon::now()->toDateTimeString());

    }
}