<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCurrencyToCountriesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('countries', function(Blueprint $table)
		{
			$table->string('currency_name')->nullable();
			$table->string('currency_symbol')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('countries', function(Blueprint $table)
		{
			$table->dropColumn('currency_name');
			$table->dropColumn('currency_symbol');
		});
	}

}
