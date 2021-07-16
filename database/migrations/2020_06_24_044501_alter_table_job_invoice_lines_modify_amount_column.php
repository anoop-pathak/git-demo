<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableJobInvoiceLinesModifyAmountColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement('ALTER TABLE `job_invoice_lines` MODIFY COLUMN `amount` DECIMAL(16,3)');
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::statement('ALTER TABLE `job_invoice_lines` MODIFY COLUMN `amount` DECIMAL(16,2)');
	}

}
