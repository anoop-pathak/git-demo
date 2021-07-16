<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableVendorBillsAddTaxAmountField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('vendor_bills', function(Blueprint $table)
		{
			$table->integer('tax_amount')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('vendor_bills',function($table){
			$table->dropColumn('tax_amount')->nullable();
		});
	}

}
