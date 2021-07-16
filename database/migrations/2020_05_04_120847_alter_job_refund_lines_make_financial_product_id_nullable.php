<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobRefundLinesMakeFinancialProductIdNullable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement('ALTER TABLE `job_refund_lines` CHANGE COLUMN `financial_product_id` `financial_product_id` INT(11) NULL');
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::statement('ALTER TABLE `job_refund_lines` CHANGE COLUMN `financial_product_id` `financial_product_id` INT(11) NOT NULL');
	}

}
