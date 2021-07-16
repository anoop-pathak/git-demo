<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableJobFinancialCalculationsModifyTotalRefundsColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement("ALTER TABLE job_financial_calculations MODIFY total_refunds DECIMAL(16,2)");
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::statement("ALTER TABLE job_financial_calculations MODIFY total_refunds Float(8,2)");
	}

}
