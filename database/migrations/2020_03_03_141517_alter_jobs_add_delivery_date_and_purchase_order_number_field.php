<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobsAddDeliveryDateAndPurchaseOrderNumberField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('jobs',function($table){
			$table->date('material_delivery_date')->nullable();
			$table->string('purchase_order_number')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('jobs',function($table){
			$table->dropColumn('material_delivery_date');
			$table->dropColumn('purchase_order_number');
		});
	}

}
