<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTradeNewsAddUrlFieldRemoveFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('trade_news', function($table){
			$table->dropColumn('description');
			$table->dropColumn('for_all_trades');
			$table->integer('trade_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('trade_news', function($table){
			$table->mediumText('description');
			$table->boolean('for_all_trades')->default(false);
			$table->dropColumn('trade_id');
		});
	}

}
