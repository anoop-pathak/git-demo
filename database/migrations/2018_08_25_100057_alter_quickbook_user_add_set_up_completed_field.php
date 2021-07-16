<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterQuickbookUserAddSetUpCompletedField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('quickbooks_user', function(Blueprint $table)
		{
			$table->boolean('setup_completed')->default(false);
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
			$table->dropColumn('setup_completed');
		});
	}

}
