<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableEstimateVentilationsAddArithmeticOperation extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('estimate_ventilations', function(Blueprint $table)
		{
			$table->string('arithmetic_operation')->default('addition')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('estimate_ventilations', function(Blueprint $table)
		{
			$table->dropColumn('arithmetic_operation');
		});
	}

}
