<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFinancialMacrosTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{

		Schema::create('financial_macros', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id');
			$table->string('macro_id');
			$table->string('macro_name');
			$table->string('type');
			$table->boolean('trade_id')->default(0);
			$table->boolean('for_all_trades')->default(0);
			$table->timestamps();
		});
		
		Schema::create('macro_details', function(Blueprint $table)
		{
			$table->engine = 'InnoDB';
			$table->increments('id');
			$table->integer('company_id');
			$table->text('macro_name');
			$table->text('macro_id');
			$table->integer('category_id');
			$table->text('product_name');
			$table->integer('quantity');
			$table->text('unit');
			$table->float('unit_cost');
			$table->text('description');
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
		Schema::drop('financial_macros');
		Schema::drop('macro_details');
	}

}
