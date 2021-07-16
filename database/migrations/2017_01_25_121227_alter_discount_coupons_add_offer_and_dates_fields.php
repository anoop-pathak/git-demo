<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterDiscountCouponsAddOfferAndDatesFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('discount_coupons', function($table) {
			$table->boolean('is_offer')->default(false);
			$table->dateTime('start_date_time')->nullable()->comment('Offer start date time');
			$table->dateTime('end_date_time')->nullable()->comment('Offer end date time');
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
			$table->dropColumn('is_offer');
			$table->dropColumn('start_date_time');
			$table->dropColumn('end_date_time');
		});
	}

}
