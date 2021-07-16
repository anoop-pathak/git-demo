<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobSchedulesAddTypeField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_schedules', function(Blueprint $table)
		{
			$table->string('type')->default('schedule');
		});

		DB::statement("ALTER TABLE job_schedules MODIFY COLUMN job_id INTEGER NULL");
		DB::statement("ALTER TABLE job_schedules MODIFY COLUMN customer_id INTEGER NULL");
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_schedules', function(Blueprint $table)
		{
			$table->dropColumn('type');
		});

		DB::statement("ALTER TABLE job_schedules MODIFY COLUMN job_id INTEGER NOT NULL");	
		DB::statement("ALTER TABLE job_schedules MODIFY COLUMN customer_id INTEGER NOT NULL");
	}

}
