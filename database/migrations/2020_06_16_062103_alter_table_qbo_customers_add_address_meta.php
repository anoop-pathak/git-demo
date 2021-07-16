<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableQboCustomersAddAddressMeta extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('qbo_customers', function(Blueprint $table)
		{
			$table->text('address_meta')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('qbo_customers', function(Blueprint $table)
		{
			$table->dropColumn('address_meta');
		});
	}

}
