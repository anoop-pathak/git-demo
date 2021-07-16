<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVendorBillLinesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('vendor_bill_lines', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('vendor_bill_id')->index();
			$table->integer('financial_account_id')->index();
			$table->float('rate', 10, 2);
			$table->float('quantity', 10, 2);
			$table->mediumText('description')->nullable();
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('vendor_bill_lines');
	}

}
