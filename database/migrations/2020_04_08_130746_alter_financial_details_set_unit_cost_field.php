<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterFinancialDetailsSetUnitCostField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement("ALTER TABLE financial_details MODIFY COLUMN unit_cost float(16,2) NULL");
		DB::statement("ALTER TABLE financial_details MODIFY COLUMN quantity float(16,2) NULL");
		DB::statement("ALTER TABLE financial_details MODIFY COLUMN selling_price float(16,2) NULL");
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::statement("ALTER TABLE financial_details MODIFY COLUMN unit_cost float(8,2) NOT NULL");
		DB::statement("ALTER TABLE financial_details MODIFY COLUMN quantity float(8,2) NOT NULL");
		DB::statement("ALTER TABLE financial_details MODIFY COLUMN selling_price float(8,2) NOT NULL");
	}

}
