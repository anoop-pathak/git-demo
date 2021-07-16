<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterMaterialListsAddThumbField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('material_lists', function(Blueprint $table)
		{
			$table->string('thumb')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('material_lists', function(Blueprint $table)
		{
			$table->dropColumn('thumb');
		});
	}

}
