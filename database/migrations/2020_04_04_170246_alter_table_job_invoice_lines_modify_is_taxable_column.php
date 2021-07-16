<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableJobInvoiceLinesModifyIsTaxableColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement('ALTER TABLE `job_invoice_lines` MODIFY COLUMN `is_taxable` TINYINT(4) NULL DEFAULT 0;');
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::statement('ALTER TABLE `job_invoice_lines` MODIFY COLUMN `is_taxable` TINYINT(4) NULL DEFAULT 1;');
	}

}
