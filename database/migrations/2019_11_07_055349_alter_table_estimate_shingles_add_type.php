<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableEstimateShinglesAddType extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('estimate_shingles', function(Blueprint $table)
		{
			$table->string('type')->after('product_id')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('estimate_shingles', function(Blueprint $table)
		{
			$table->dropColumn('type');
		});
	}

}
