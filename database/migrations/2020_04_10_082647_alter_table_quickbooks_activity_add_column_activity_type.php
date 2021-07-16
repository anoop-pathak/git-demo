<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableQuickbooksActivityAddColumnActivityType extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('quickbooks_activity', function(Blueprint $table) {
			$table->string('activity_type')->nullable()->after('msg');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('quickbooks_activity', function(Blueprint $table) {
			$table->dropColumn('activity_type');
		});
	}

}
