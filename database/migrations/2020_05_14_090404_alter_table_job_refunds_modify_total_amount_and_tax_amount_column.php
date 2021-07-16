<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableJobRefundsModifyTotalAmountAndTaxAmountColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement("ALTER TABLE job_refunds MODIFY COLUMN total_amount DECIMAL(16,2) NULL");
		DB::statement("ALTER TABLE job_refunds MODIFY COLUMN tax_amount DECIMAL(16,2) NULL");
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::statement("ALTER TABLE job_refunds MODIFY COLUMN total_amount FLOAT(16,2) NULL");
		DB::statement("ALTER TABLE job_refunds MODIFY COLUMN tax_amount FLOAT(16,2) NULL");
	}

}
