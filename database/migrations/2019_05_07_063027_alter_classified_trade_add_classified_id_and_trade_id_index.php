<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterClassifiedTradeAddClassifiedIdAndTradeIdIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('classified_trade', function(Blueprint $table) {
			if (!isIndexExists('classified_trade', 'classified_trade_classified_id_index')) {
				$table->index('classified_id');
			}
			if (!isIndexExists('classified_trade', 'classified_trade_trade_id_index')) {
				$table->index('trade_id');
			}
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('classified_trade', function(Blueprint $table) {
			$table->dropIndex('classified_trade_classified_id_index');
			$table->dropIndex('classified_trade_trade_id_index');
		});
	}

}
