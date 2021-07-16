<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterQuickbooksUserChangeCompanyIdField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('quickbooks_user', function(Blueprint $table)
		{
			DB::statement('ALTER TABLE `quickbooks_user` MODIFY `company_id` INTEGER  NULL;');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('quickbooks_user', function(Blueprint $table)
		{
			DB::statement('ALTER TABLE `quickbooks_user` MODIFY `company_id` INTEGER  NOT NULL;');
		});
	}
}
