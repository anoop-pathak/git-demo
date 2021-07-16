<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterEstimationsAddShareOnHopField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('estimations', function(Blueprint $table)
		{
			$table->boolean('share_on_hop')->default(false);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('estimations', function(Blueprint $table)
		{
			$table->dropColumn('share_on_hop');
		});
	}

}
