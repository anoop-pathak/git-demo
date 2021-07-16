<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableEstimationsAddEstimationTypeColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('estimations', function(Blueprint $table) {
			$table->string('estimation_type')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('estimations', function(Blueprint $table) {
			$table->dropColumn('estimation_type');
		});
	}

}
