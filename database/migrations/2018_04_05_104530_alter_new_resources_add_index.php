<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterNewResourcesAddIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('new_resources', function($table)
		{
			if (!isIndexExists('new_resources', 'new_resources_company_id_index')) {
				
				$table->index('company_id');
			}

			if (!isIndexExists('new_resources', 'new_resources_name_index')) {
				
				$table->index('name');
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
		Schema::table('new_resources', function($table)
		{
			$table->dropIndex('new_resources_company_id_index');
			$table->dropIndex('new_resources_name_index');
		});
	}

}
