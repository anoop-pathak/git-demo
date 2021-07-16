<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableVendorBillsAddAddressColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('vendor_bills', function(Blueprint $table)
		{
			$table->dropColumn('bill_address_id');
			$table->text('address')->nullable();
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
			$table->dropColumn('address')->nullable();
		});
	}

}
