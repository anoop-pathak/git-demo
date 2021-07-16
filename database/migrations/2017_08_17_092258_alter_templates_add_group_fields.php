<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTemplatesAddGroupFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('templates', function($table)
		{
			$table->string('group_name')->nullable()->after('title');
			$table->string('group_id')->nullable()->after('title');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('templates', function($table)
		{
			$table->dropColumn('group_name');
			$table->dropColumn('group_id');
		});
	}

}
