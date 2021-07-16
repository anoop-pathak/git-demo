<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableQuickbookMappedJobsChangeDataType extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement('ALTER TABLE quickbook_mapped_jobs MODIFY COLUMN qb_customer_id VARCHAR(256) NULL');
		DB::statement('ALTER TABLE quickbook_mapped_jobs MODIFY COLUMN qb_job_id VARCHAR(256) NULL');
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::statement('ALTER TABLE quickbook_mapped_jobs MODIFY COLUMN qb_customer_id INTEGER NULL');
		DB::statement('ALTER TABLE quickbook_mapped_jobs MODIFY COLUMN qb_job_id INTEGER NULL');
	}

}
