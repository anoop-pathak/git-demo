<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobsAddIndexOnAddressId extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('jobs', function($table)
		{
			if (!isIndexExists('jobs', 'jobs_address_id_index')) {
				
				$table->index('address_id');
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
		Schema::table('jobs', function($table)
		{
			$table->dropIndex('jobs_address_id_index');
		});
	}

}
