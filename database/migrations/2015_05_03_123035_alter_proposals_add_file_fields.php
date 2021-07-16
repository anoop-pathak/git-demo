<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterProposalsAddFileFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('proposals',function($table){
			$table->boolean('is_file')->default(false);
			$table->string('file_name')->nullable();
			$table->string('file_path')->nullable();
			$table->string('file_mime_type')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('proposals',function($table){
			$table->dropColumn('is_file');
			$table->dropColumn('file_name');
			$table->dropColumn('file_path');
			$table->dropColumn('file_mime_type');
		});
	}

}
