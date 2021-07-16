<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterFinancialDetailsAddWorksheetId extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('financial_details', function(Blueprint $table)
		{
			$table->integer('worksheet_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('financial_details', function(Blueprint $table)
		{
			$table->dropColumn('worksheet_id');
		});
	}

}
