<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddWorksheetMultiDescriptionFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('financial_details', function(Blueprint $table)
		{
			$table->text('tier1_description')->after('tier3')->nullable();
			$table->text('tier2_description')->after('tier1_description')->nullable();
			$table->text('tier3_description')->after('tier2_description')->nullable();
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
			$table->dropColumn('tier1_description');
			$table->dropColumn('tier2_description');
			$table->dropColumn('tier3_description');
		});
	}

}
