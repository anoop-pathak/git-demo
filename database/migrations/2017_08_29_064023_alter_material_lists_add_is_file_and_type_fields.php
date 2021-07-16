<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterMaterialListsAddIsFileAndTypeFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('material_lists', function(Blueprint $table)
		{
			$table->boolean('is_file')->default(false);
			$table->string('type')->default('material_list')->after('job_id');
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
			$table->dropColumn('is_file');
			$table->dropColumn('type');
		});
	}

}
