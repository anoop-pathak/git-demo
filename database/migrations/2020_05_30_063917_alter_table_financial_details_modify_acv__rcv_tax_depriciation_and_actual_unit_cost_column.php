<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableFinancialDetailsModifyAcvRcvTaxDepriciationAndActualUnitCostColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement("ALTER TABLE financial_details MODIFY COLUMN actual_unit_cost DECIMAL(16,2)");
		DB::statement("ALTER TABLE financial_details MODIFY COLUMN acv DECIMAL(16,2)");
		DB::statement("ALTER TABLE financial_details MODIFY COLUMN rcv DECIMAL(16,2)");
		DB::statement("ALTER TABLE financial_details MODIFY COLUMN tax DECIMAL(16,2)");
		DB::statement("ALTER TABLE financial_details MODIFY COLUMN depreciation DECIMAL(16,2)");
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::statement("ALTER TABLE financial_details MODIFY COLUMN actual_unit_cost FLOAT(8,2)");
		DB::statement("ALTER TABLE financial_details MODIFY COLUMN acv FLOAT(8,2)");
		DB::statement("ALTER TABLE financial_details MODIFY COLUMN rcv FLOAT(8,2)");
		DB::statement("ALTER TABLE financial_details MODIFY COLUMN tax FLOAT(8,2)");
		DB::statement("ALTER TABLE financial_details MODIFY COLUMN depreciation FLOAT(8,2)");
	}

}
