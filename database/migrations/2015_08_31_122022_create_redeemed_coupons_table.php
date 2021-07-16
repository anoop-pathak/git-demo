<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRedeemedCouponsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('redeemed_coupons', function(Blueprint $table)
		{
			$table->engine = 'InnoDB';
			$table->increments('id');
			$table->integer('company_id');
			$table->string('coupon_code');
			$table->text('coupon_detail');
			$table->date('start_date')->nullable();
			$table->date('end_date')->nullable();
			$table->boolean('is_active');
			$table->string('valid_for'); //setup_fee, monthly_fee
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
		Schema::drop('redeemed_coupons');
	}

}
