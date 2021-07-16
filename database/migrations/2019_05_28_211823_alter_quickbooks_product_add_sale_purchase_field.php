<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterQuickbooksProductAddSalePurchaseField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('quickbooks_product', function(Blueprint $table)
		{
			$table->string('sale_or_purchase_account_id')->nullable();
			$table->string('sale_or_purchase_account_name')->nullable();
			$table->string('sale_or_purchase_price')->nullable();
			$table->string('qb_desktop_sequence_number')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('quickbooks_product', function(Blueprint $table)
		{
			$table->dropColumn('sale_or_purchase_account_id');
			$table->dropColumn('sale_or_purchase_account_name');
			$table->dropColumn('sale_or_purchase_price');
			$table->dropColumn('qb_desktop_sequence_number');
		});
	}

}
