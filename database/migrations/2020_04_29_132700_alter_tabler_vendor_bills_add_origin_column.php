<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablerVendorBillsAddOriginColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	*/
	public function up()
	{
		Schema::table('vendor_bills', function(Blueprint $table)
		{
			$table->tinyInteger('origin')->default(0);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	*/
	public function down()
	{
		Schema::table('vendor_bills', function(Blueprint $table)
		{
			$table->dropColumn('origin');
		});
	}

}
