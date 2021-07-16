<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterCompanyCamClientsAddIndexes extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('company_cam_clients', function($table)
		{
			if (!isIndexExists('company_cam_clients', 'company_cam_clients_company_id_index')) {
				
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
		Schema::table('company_cam_clients', function($table)
		{
			$table->dropIndex('company_cam_clients_company_id_index');
		});
	}

}
