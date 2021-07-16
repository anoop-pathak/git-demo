<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobNotesAddSoftDeleteField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_notes', function(Blueprint $table){
			$table->integer('deleted_by')->nullable();
			$table->softDeletes();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_notes', function(Blueprint $table){
			$table->dropColumn('deleted_by');
			$table->dropColumn('deleted_at');
		});
	}

}
