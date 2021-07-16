<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableQboCustomersAddLevelColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('qbo_customers', function(Blueprint $table) {
			$table->integer('level')->nullable()->after('qb_parent_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('qbo_customers', function(Blueprint $table) {
			$table->dropColumn('level');
		});
	}

}
