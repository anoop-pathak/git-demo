<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterDiscountCouponsAddIsActiveField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('discount_coupons', function($table) {
			$table->boolean('is_active')->default(true)->after('id');
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
			$table->dropColumn('is_active');
		});
	}

}
