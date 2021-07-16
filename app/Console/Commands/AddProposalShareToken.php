<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Proposal;
use Carbon\Carbon;

class AddProposalShareToken extends Command {
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:add_proposal_share_token';

    /**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Add share token in those proposals which are saved without token.';

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
		$this->info('Token updated in DB by Query. it will take some time. DateTime:' . Carbon::now());
		Proposal::withTrashed()
			->whereNull('token')
			->chunk(100, function($proposals) {
				foreach ($proposals as $proposal) {
					$proposal->token = generateUniqueToken();
					$proposal->save();
				}
			});
		$this->info('Token for proposals updated successfully');
	}
}