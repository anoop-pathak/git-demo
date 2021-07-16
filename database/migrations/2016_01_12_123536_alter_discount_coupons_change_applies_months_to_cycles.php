<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterDiscountCouponsChangeAppliesMonthsToCycles extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('discount_coupons', function($table){
			$table->renameColumn('applies_for_months', 'cycles');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('discount_coupons', function($table){
			$table->renameColumn('cycles','applies_for_months');
		});
	}

}
