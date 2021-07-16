<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableQuickbookMappedJobsAddActionRequiredJobColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('quickbook_mapped_jobs', function(Blueprint $table) {
			$table->boolean('action_required_job')->default(false);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('quickbook_mapped_jobs', function(Blueprint $table) {
			$table->dropColumn('action_required_job');
		});
	}

}
