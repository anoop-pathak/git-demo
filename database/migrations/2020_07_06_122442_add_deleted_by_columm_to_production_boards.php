<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddDeletedByColummToProductionBoards extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('production_boards', function(Blueprint $table)
		{
			$table->softDeletes()->after('updated_at');
			$table->integer('deleted_by')->after('deleted_at');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('production_boards', function(Blueprint $table)
		{
			$table->dropColumn('deleted_at');
			$table->dropColumn('deleted_by');
		});
	}

}
