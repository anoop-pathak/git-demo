<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobWorkflowJobWorkflowHistoryJobNotesChangeDateTypeOfStageCode extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement('ALTER TABLE job_workflow MODIFY COLUMN current_stage VARCHAR(255)');
		DB::statement('ALTER TABLE job_workflow_history MODIFY COLUMN stage VARCHAR(255)');
		DB::statement('ALTER TABLE job_notes MODIFY COLUMN stage_code VARCHAR(255)');
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::statement('ALTER TABLE job_workflow MODIFY COLUMN current_stage int');
		DB::statement('ALTER TABLE job_workflow_history MODIFY COLUMN stage int');
		DB::statement('ALTER TABLE job_notes MODIFY COLUMN stage_code int');
	}

}
