<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTemplatesAddGroupOrderField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('templates', function(Blueprint $table)
		{
			$table->integer('group_order')->default(false);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('templates', function(Blueprint $table)
		{
			$table->dropColumn('group_order');
		});
	}

}
