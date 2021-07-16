<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterCompanyStateAddTaxRateField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('company_state', function(Blueprint $table)
		{
			$table->string('tax_rate')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('company_state', function(Blueprint $table)
		{
			$table->dropColumn('tax_rate');
		});
	}

}
