<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterEstimationsAddSkymeasureIdField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('estimations', function($table)
		{
			$table->string('sm_order_id')->nullable()->after('worksheet_id')->comment('Skymeasure order Id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('estimations', function($table)
		{
			$table->dropColumn('sm_order_id');
		});
	}

}
