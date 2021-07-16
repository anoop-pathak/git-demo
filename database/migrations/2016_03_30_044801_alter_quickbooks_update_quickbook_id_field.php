<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterQuickbooksUpdateQuickbookIdField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('quickbooks', function(Blueprint $table)
		{
			 DB::statement('ALTER TABLE `quickbooks` MODIFY `quickbook_id` varchar(255)  NULL;');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('quickbooks', function(Blueprint $table)
		{
			 DB::statement('ALTER TABLE `quickbooks` MODIFY `quickbook_id` varchar(255)  NOT NULL;');
		});
	}

}
