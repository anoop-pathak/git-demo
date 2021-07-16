<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableQboCustomersAddFinancialEntitiesCountColumns extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('qbo_customers', function(Blueprint $table) {
			$table->integer('total_invoice_count')->nullable();
			$table->integer('total_payment_count')->nullable();
			$table->integer('total_credit_count')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('qbo_customers', function(Blueprint $table) {
			$table->dropColumn('total_invoice_count');
			$table->dropColumn('total_payment_count');
			$table->dropColumn('total_credit_count');
		});
	}

}
