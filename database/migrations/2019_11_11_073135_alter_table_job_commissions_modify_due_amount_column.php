<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableJobCommissionsModifyDueAmountColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::select(DB::raw('ALTER TABLE `job_commissions` CHANGE COLUMN `due_amount` `due_amount` decimal(16,2) NULL;'));
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::select(DB::raw('ALTER TABLE `job_commissions` CHANGE COLUMN `due_amount` `due_amount` decimal(8,2) NULL;'));
	}

}
