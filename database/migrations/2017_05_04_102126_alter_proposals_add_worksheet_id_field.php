<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterProposalsAddWorksheetIdField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('proposals', function($table){
			$table->integer('worksheet_id')->nullable()->after('is_mobile');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('proposals', function($table){
			$table->dropColumn('worksheet_id');
		});
	}

}
