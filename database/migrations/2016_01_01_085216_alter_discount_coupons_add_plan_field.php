<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterDiscountCouponsAddPlanField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('discount_coupons', function(Blueprint $table){
			$table->string('type');
			$table->integer('product_id')->nullable();
			$table->string('plan_code')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('discount_coupons', function(Blueprint $table){
			$table->dropColumn('type');
			$table->dropColumn('product_id');
			$table->dropColumn('plan_code');
		});
	}

}
