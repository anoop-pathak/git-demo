<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterHoverJobsTableAddHoverField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('hover_jobs', function(Blueprint $table)
		{
			$table->string('name');
			$table->string('customer_name');
			$table->string('customer_email');
			$table->string('customer_phone')->nullable();
			$table->string('location_line_1')->nullable();
			$table->string('location_line_2')->nullable();
			$table->string('location_city')->nullable();
			$table->string('location_country')->nullable();
			$table->string('location_region')->nullable();
			$table->string('location_postal_code')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('hover_jobs', function(Blueprint $table)
		{
			$table->dropColumn('name');
			$table->dropColumn('customer_name');
			$table->dropColumn('customer_email');
			$table->dropColumn('customer_phone');
			$table->dropColumn('location_line_1');
			$table->dropColumn('location_line_2');
			$table->dropColumn('location_city');
			$table->dropColumn('location_country');
			$table->dropColumn('location_region');
			$table->dropColumn('location_postal_code');
		});
	}

}
