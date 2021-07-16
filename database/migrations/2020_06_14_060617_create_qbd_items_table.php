<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQbdItemsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('qbd_items', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id');
			$table->string('name');
			$table->integer('qbd_uom_id')->nullable();
			$table->integer('qbd_sales_tax_code_id')->nullable();
			$table->integer('sales_financial_account_id')->nullable();
			$table->integer('purchase_financial_account_id')->nullable();
			$table->integer('sales_or_purchase_financial_account_id')->nullable();
			$table->decimal('price', 16, 2)->nullable();
			$table->string('description')->nullable();
			$table->string('price_percent')->nullable();
			$table->string('sale_description')->nullable();
			$table->decimal('sale_price', 16, 2)->nullable();
			$table->integer('level')->default(0);
			$table->decimal('purchase_cost')->nullable();
			$table->string('purchase_description')->nullable();
			$table->string('qb_desktop_id')->nullable();
			$table->string('qb_desktop_sequence_number')->nullable();
			$table->softDeletes();
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
		Schema::drop('qbd_items');
	}

}
