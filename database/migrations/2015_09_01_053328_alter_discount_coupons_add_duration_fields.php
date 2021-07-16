<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterDiscountCouponsAddDurationFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('discount_coupons', function($table) {
			$table->boolean('single_use')->default(false);
			$table->string('applies_for_months')->nullable();
			$table->string('duration')->nullable();
			$table->string('temporal_unit')->nullable();
			$table->string('temporal_amount')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('discount_coupons', function($table) {
			$table->dropColumn('single_use');
			$table->dropColumn('applies_for_months');
			$table->dropColumn('duration');
			$table->dropColumn('temporal_unit');
			$table->dropColumn('temporal_amount');
		});
	}

}
