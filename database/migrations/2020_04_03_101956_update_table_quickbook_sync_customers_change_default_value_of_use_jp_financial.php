<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateTableQuickbookSyncCustomersChangeDefaultValueOfUseJpFinancial extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement('ALTER TABLE `quickbook_sync_customers` CHANGE COLUMN `use_jp_financial` `use_jp_financial` Boolean NULL DEFAULT 0;');
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::statement('ALTER TABLE `quickbook_sync_customers` CHANGE COLUMN `use_jp_financial` `use_jp_financial` Boolean NULL DEFAULT 1;');
	}

}
