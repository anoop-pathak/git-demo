<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterProposalsAddIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('proposals', function($table) 
		{
			if (!isIndexExists('proposals', 'proposals_company_id_index')) {
				
				$table->index('company_id');
			}

			if (!isIndexExists('proposals', 'proposals_job_id_index')) {
				
				$table->index('job_id');
			}

			if (!isIndexExists('proposals', 'proposals_status_index')) {
				
				$table->index('status');
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
		Schema::table('proposals', function($table) 
		{
			$table->dropindex('proposals_company_id_index');
			$table->dropindex('proposals_job_id_index');
			$table->dropindex('proposals_status_index');
		});
	}

}
