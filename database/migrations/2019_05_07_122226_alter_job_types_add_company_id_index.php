<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobTypesAddCompanyIdIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_types', function(Blueprint $table) {
			if (!isIndexExists('job_types', 'job_types_company_id_index')) {
				$table->index('company_id');
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
		Schema::table('job_types', function(Blueprint $table) {
			$table->dropIndex('job_types_company_id_index');
		});
	}

}
