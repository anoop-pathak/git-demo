<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableJobRefundLinesModifyQuantityAndRateColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement("ALTER TABLE job_refund_lines MODIFY COLUMN quantity DECIMAL(16,2)");
		DB::statement("ALTER TABLE job_refund_lines MODIFY COLUMN rate DECIMAL(16,2)");
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::statement("ALTER TABLE job_refund_lines MODIFY COLUMN quantity FLOAT(10,2)");
		DB::statement("ALTER TABLE job_refund_lines MODIFY COLUMN rate FLOAT(10,2)");
	}

}
