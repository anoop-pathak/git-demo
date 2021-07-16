<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableJobInvoiceLinesModifyQuantityColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement("ALTER TABLE `job_invoice_lines` MODIFY COLUMN `quantity` VARCHAR(10) DEFAULT 1");
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::statement("ALTER TABLE `job_invoice_lines` MODIFY COLUMN `quantity` VARCHAR(7) DEFAULT 1");
	}

}
