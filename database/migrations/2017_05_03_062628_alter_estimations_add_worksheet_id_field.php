<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterEstimationsAddWorksheetIdField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('estimations', function($table){
			$table->integer('worksheet_id')->nullable()->after('ev_file_type_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('estimations', function($table){
			$table->dropColumn('worksheet_id');
		});
	}

}
