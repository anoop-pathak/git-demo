<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Events\SubContractorDeleted;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;

class DeleteSubcontractorUsers extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:delete_invalid_sub_contractors';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Delete invalid subcontracto users.';

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
		$users = User::where('id', '>', '18334')
			->where('company_id', 1139)
			->get();

		foreach ($users as $key => $user) {
			$user->delete();

			Event::fire('JobProgress.SubContractors.Events.SubContractorDeleted', new SubContractorDeleted($user));
		}

		foreach ($users as $key => $user) {
			DB::table('users')->where('id', $user->id)->update([
				'email' => $user->id.'_'.$user->email
			]);
		}
	}
}
