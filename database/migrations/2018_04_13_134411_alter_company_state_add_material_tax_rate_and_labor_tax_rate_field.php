<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterCompanyStateAddMaterialTaxRateAndLaborTaxRateField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('company_state', function(Blueprint $table)
		{
			$table->string('material_tax_rate')->nullable();
			$table->string('labor_tax_rate')->nullable();
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
			$table->dropColumn('material_tax_rate');
			$table->dropColumn('labor_tax_rate');
		});
	}

}
