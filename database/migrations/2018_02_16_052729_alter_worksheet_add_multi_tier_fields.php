<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterWorksheetAddMultiTierFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('financial_details', function($table)
		{
			$table->string('tier1')->nullable()->after('id');
			$table->string('tier2')->nullable()->after('tier1');
			$table->string('tier3')->nullable()->after('tier2');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('financial_details', function($table)
		{
			$table->dropColumn('tier1');
			$table->dropColumn('tier2');
			$table->dropColumn('tier3');
		});
	}

}
