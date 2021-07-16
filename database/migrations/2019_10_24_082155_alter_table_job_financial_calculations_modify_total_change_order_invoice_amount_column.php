<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableJobFinancialCalculationsModifyTotalChangeOrderInvoiceAmountColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::select(DB::raw('ALTER TABLE `job_financial_calculations` CHANGE COLUMN `total_change_order_invoice_amount` `total_change_order_invoice_amount` decimal(16,2) NULL;'));
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::select(DB::raw('ALTER TABLE `job_financial_calculations` CHANGE COLUMN `total_change_order_invoice_amount` `total_change_order_invoice_amount` decimal(8,2) NULL;'));
	}

}
