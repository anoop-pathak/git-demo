<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\User;
use Exception;

class SetBlankPasswordAsNull extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:set_blank_password_as_null';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Set Blank Password Of SubContractor Users As Null.';

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
		$startedAt = Carbon::now();
		$this->info("Command started at: {$startedAt}");
		$userIds = [];
		passwordNull:{
			User::withTrashed()
				->whereIn('group_id', [User::GROUP_SUB_CONTRACTOR, User::GROUP_SUB_CONTRACTOR_PRIME])
				->whereNotNull('password')
				->whereNotIn('id', $userIds)
				->orderBy('id', 'asc')
				->select('id', 'password')
				->chunk(100, function($users) use(&$userIds) {
					foreach($users as $user) {
						try {
							$userIds[] = $user->id;
							if(!password_verify("", $user->password)) continue;

							User::withTrashed()->whereId($user->id)->update(['password' => null]);

							$this->info("----- Password updated. User id: $user->id -----");

						} catch (Exception $e) {
							$this->info("----- Password not updated for user id: $user->id -----");
							$msg = $e->getMessage();
							$this->info("----- Error: $msg -----");

						}
					}
				});
		}
		$userCount = User::withTrashed()
				->whereIn('group_id', [User::GROUP_SUB_CONTRACTOR, User::GROUP_SUB_CONTRACTOR_PRIME])
				->whereNotNull('password')
				->whereNotIn('id', $userIds)
				->count();
		if($userCount) {
			goto passwordNull;
		}

		$completedAt = Carbon::now();
		$this->info("Command completed at: {$completedAt}");
	}
}