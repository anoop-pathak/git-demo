<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTempImportCustomersAddQuickbookField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('temp_import_customers', function(Blueprint $table)
		{
			$table->boolean('quickbook')->default(0);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('temp_import_customers', function(Blueprint $table)
		{
			$table->dropColumn('quickbook');
		});
	}

}
