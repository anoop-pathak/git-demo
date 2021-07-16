<?php

namespace App\Console\Commands;

use App\Models\QBDesktopUser;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use App\Services\QuickBookDesktop\QBDesktopUtilities;
class CreateDiscounItemQBDesktop extends Command {
 	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:create_discount_item_quickbook_desktop';
 	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create discount item on quickbook desktop.';
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
		$qbUsers = QBDesktopUser::all();
		foreach ($qbUsers as $qbUsers) {
			QBDesktopUtilities::createDiscountItem($qbUsers->qb_username);
		}
 	}
}