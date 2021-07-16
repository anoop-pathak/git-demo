<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterEtimationsAddIndexForCompanyIdJobIdMultiPage extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('estimations', function($table) 
		{
			if (!isIndexExists('estimations', 'estimations_company_id_index')) {
				
				$table->index('company_id');
			}

			if (!isIndexExists('estimations', 'estimations_job_id_index')) {
				
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
		Schema::table('estimations', function($table) 
		{
			$table->dropindex('estimations_company_id_index');
			$table->dropindex('estimations_job_id_index');
		});
	}

}
