<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ForAllTradesIntoScriptsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('scripts', function(Blueprint $table) {
			$table->boolean('for_all_trades')->after('description');
		});

		DB::statement("UPDATE scripts SET for_all_trades = 1");
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('scripts', function(Blueprint $table) {
			$table->dropColumn('for_all_trades');
		});
	}

}
