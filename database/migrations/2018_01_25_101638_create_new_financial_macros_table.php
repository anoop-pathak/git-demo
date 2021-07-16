<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNewFinancialMacrosTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		// Schema::dropIfExists('financial_macros');
		// Schema::create('financial_macros', function(Blueprint $table)
		// {
		// 	$table->increments('id');
		// 	$table->integer('company_id');
		// 	$table->string('macro_id');
		// 	$table->string('macro_name');
		// 	$table->string('type');
		// 	$table->boolean('trade_id')->default(0);
		// 	$table->boolean('for_all_trades')->default(0);
		// 	$table->timestamps();
		// });

		// DB::statement("Insert into financial_macros (macro_id, company_id, macro_name, type, trade_id, for_all_trades, created_at, updated_at) Select macro_id, company_id, macro_name, type, trade_id, for_all_trades, created_at, updated_at from macro_details GROUP BY macro_id");
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		// Schema::drop('financial_macros');
	}

}
