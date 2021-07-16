<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableEstimateStructuresAddTypeId extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('estimate_structures', function(Blueprint $table)
		{
			$table->dropColumn('name');
			$table->dropColumn('type');
			$table->integer('type_id')->after('company_id')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('estimate_structures', function(Blueprint $table) {
			$table->dropColumn('type_id');
			$table->string('name')->nullable();
			$table->string('type')->nullable();
		});
	}

}
