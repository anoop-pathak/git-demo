<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterThirdPartyToolsAddAllTradeField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('third_party_tools', function($table){
			$table->boolean('for_all_trades')->default(false);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('third_party_tools', function($table){
			$table->dropColumn('for_all_trades');
		});
	}

}
