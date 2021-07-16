<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterSubscriptionAddRemainingCyclesCouponRedeemedCouponValidTillFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('subscriptions', function(Blueprint $table){
			$table->integer('quantity')->after('amount')->nullable();
			$table->string('next_renewal_plan')->nullable();
			$table->dateTime('next_renewal_date')->nullable();
			$table->string('redeemed_monthly_fee_coupon')->nullable();
			$table->dateTime('coupon_valid_till')->nullable();
			$table->string('remaining_cycles')->default('unlimited')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('subscriptions', function(Blueprint $table){
			$table->dropColumn('quantity');
			$table->dropColumn('next_renewal_plan');
			$table->dropColumn('next_renewal_date');
			$table->dropColumn('remaining_cycles');
			$table->dropColumn('redeemed_monthly_fee_coupon');
			$table->dropColumn('coupon_valid_till');
		});
	}

}
