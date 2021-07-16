<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablePredefinedEstimateTypesAddIcon extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('predefined_estimate_types', function(Blueprint $table)
		{
			$table->string('icon')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('predefined_estimate_types', function(Blueprint $table)
		{
			$table->dropColumn('icon');
		});
	}

}
