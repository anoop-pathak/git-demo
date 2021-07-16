<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableEstimatePitchAddName extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('estimate_pitch', function(Blueprint $table)
		{
			$table->dropColumn('start_point');
			$table->dropColumn('end_point');
			$table->string('name')->after('company_id')->nullable();
			 $table->softDeletes();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('estimate_pitch', function(Blueprint $table) {
			$table->dropColumn('name');
			$table->integer('start_point')->nullable();
			$table->integer('end_point')->nullable();
		});
	}

}
