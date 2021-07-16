<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobFollowUpAddIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_follow_up', function($table)
		{
			if (!isIndexExists('job_follow_up', 'job_follow_up_company_id_index')) {
				
				$table->index('company_id');
			}

			if (!isIndexExists('job_follow_up', 'job_follow_up_customer_id_index')) {
				
				$table->index('customer_id');
			}

			if (!isIndexExists('job_follow_up', 'job_follow_up_job_id_index')) {
				
				$table->index('job_id');
			}
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_follow_up', function($table)
		{
			$table->dropIndex('job_follow_up_company_id_index');
			$table->dropIndex('job_follow_up_customer_id_index');
			$table->dropIndex('job_follow_up_job_id_index');
		});
	}

}
