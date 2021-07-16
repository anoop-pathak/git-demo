<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobFinancialCalculationsAddPlSheetTotalField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_financial_calculations', function(Blueprint $table)
		{
			$table->decimal('pl_sheet_total', 16, 2)->default(0)->comment('job profit loss cost');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_financial_calculations', function(Blueprint $table)
		{
			$table->dropColumn('pl_sheet_total');
		});
	}

}
