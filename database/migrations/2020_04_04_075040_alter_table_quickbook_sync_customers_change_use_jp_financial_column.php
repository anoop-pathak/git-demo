<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableQuickbookSyncCustomersChangeUseJpFinancialColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement('ALTER TABLE `quickbook_sync_customers` CHANGE COLUMN `use_jp_financial` `retain_financial` Integer NULL DEFAULT 0;');
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::statement('ALTER TABLE `quickbook_sync_customers` CHANGE COLUMN `retain_financial` `use_jp_financial` Boolean NULL DEFAULT 0;');
	}

}
