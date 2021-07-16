<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeAmountDataType extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement('ALTER TABLE job_pricing_history MODIFY COLUMN amount decimal(16,2) NOT NULL');
		DB::statement('ALTER TABLE change_order_entities MODIFY COLUMN amount decimal(16,2) NOT NULL');
		DB::statement('ALTER TABLE job_invoices MODIFY COLUMN amount decimal(16,2) NOT NULL');
		DB::statement('ALTER TABLE job_commissions MODIFY COLUMN amount decimal(16,2) NOT NULL');
		DB::statement('ALTER TABLE market_source_spents MODIFY COLUMN amount decimal(16,2) NULL');
		DB::statement('ALTER TABLE job_credits MODIFY COLUMN amount decimal(16,2) NOT NULL');
		DB::statement('ALTER TABLE job_payments MODIFY COLUMN payment decimal(16,2) NOT NULL');
		DB::statement('ALTER TABLE change_orders MODIFY COLUMN total_amount decimal(16,2) NOT NULL');

		//financial calculation
		DB::statement('ALTER TABLE job_financial_calculations MODIFY COLUMN total_job_amount decimal(16,2) NOT NULL');
		DB::statement('ALTER TABLE job_financial_calculations MODIFY COLUMN total_change_order_amount decimal(16,2) NOT NULL');
		DB::statement('ALTER TABLE job_financial_calculations MODIFY COLUMN total_amount decimal(16,2) NOT NULL');
		DB::statement('ALTER TABLE job_financial_calculations MODIFY COLUMN total_received_payemnt decimal(16,2) NOT NULL');
		DB::statement('ALTER TABLE job_financial_calculations MODIFY COLUMN total_credits decimal(16,2) NOT NULL');
		DB::statement('ALTER TABLE job_financial_calculations MODIFY COLUMN pending_payment decimal(16,2) NOT NULL');
		DB::statement('ALTER TABLE job_financial_calculations MODIFY COLUMN total_commission decimal(16,2) NOT NULL');
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::statement('ALTER TABLE job_pricing_history MODIFY COLUMN amount float(8,2) NOT NULL');
		DB::statement('ALTER TABLE change_order_entities MODIFY COLUMN amount float(8,2) NOT NULL');
		DB::statement('ALTER TABLE job_invoices MODIFY COLUMN amount float(8,2) NOT NULL');
		DB::statement('ALTER TABLE job_commissions MODIFY COLUMN amount float(8,2) NOT NULL');
		DB::statement('ALTER TABLE market_source_spents MODIFY COLUMN amount float(8,2) null');
		DB::statement('ALTER TABLE job_credits MODIFY COLUMN amount float(8,2) NOT NULL');
		DB::statement('ALTER TABLE job_payments MODIFY COLUMN payment float(8,2) NOT NULL');
		DB::statement('ALTER TABLE change_orders MODIFY COLUMN total_amount float(8,2) NOT NULL');
		
		//financial calculation
		DB::statement('ALTER TABLE job_financial_calculations MODIFY COLUMN total_job_amount float(8,2) NOT NULL');
		DB::statement('ALTER TABLE job_financial_calculations MODIFY COLUMN total_change_order_amount float(10,2) NOT NULL');
		DB::statement('ALTER TABLE job_financial_calculations MODIFY COLUMN total_amount float(8,2) NOT NULL');
		DB::statement('ALTER TABLE job_financial_calculations MODIFY COLUMN total_received_payemnt float(8,2) NOT NULL');
		DB::statement('ALTER TABLE job_financial_calculations MODIFY COLUMN total_credits float(8,2) NOT NULL');
		DB::statement('ALTER TABLE job_financial_calculations MODIFY COLUMN pending_payment float(8,2) NOT NULL');
		DB::statement('ALTER TABLE job_financial_calculations MODIFY COLUMN total_commission float(8,2) NOT NULL');
	}

}
