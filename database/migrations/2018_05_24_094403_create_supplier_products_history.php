<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSupplierProductsHistory extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('supplier_products_history', function(Blueprint $table)
		{
			$table->engine = 'InnoDB';
			$table->increments('id');
			$table->integer('company_id');
			$table->integer('product_id')->comment("Financial Product Id");
			$table->integer('category_id');
			$table->string('name');
			$table->string('code')->nullable();
			$table->string('unit');
			$table->float('unit_cost');
			$table->text('description')->nullable();
			$table->integer('supplier_id');
			$table->text('additional_data')->nullable();
			$table->string('batch_id', 100);
			$table->timestamp('affected_from')->nullable();
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
		Schema::drop('supplier_products_history');
	}

}
