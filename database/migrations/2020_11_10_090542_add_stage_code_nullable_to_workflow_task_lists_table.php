<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddStageCodeNullableToWorkflowTaskListsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement("ALTER TABLE workflow_task_lists MODIFY  stage_code varchar(255) NULL;");
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::statement("ALTER TABLE workflow_task_lists MODIFY  stage_code varchar(255)  NOT NULL;");
	}

}
