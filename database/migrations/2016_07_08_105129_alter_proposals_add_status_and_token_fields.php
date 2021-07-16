<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterProposalsAddStatusAndTokenFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('proposals', function($table) {
			$table->string('status')->default('draft');;
			$table->string('token')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('proposals', function($table) {
			$table->dropColumn('status');
			$table->dropColumn('token');
		});
	}

}
