<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexingOnAfIdInAfCustomersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('af_customers', function(Blueprint $table)
		{
			$table->index('af_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('af_customers', function(Blueprint $table)
		{
			$table->dropIndex('af_customers_af_id_index');
		});
	}

}
