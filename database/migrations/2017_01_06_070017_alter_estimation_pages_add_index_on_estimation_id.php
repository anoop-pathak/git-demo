<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterEstimationPagesAddIndexOnEstimationId extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('estimation_pages', function($table) 
		{
			if (!isIndexExists('estimation_pages', 'estimation_pages_estimation_id_index')) {
				
				$table->index('estimation_id');
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
		Schema::table('estimation_pages', function($table) 
		{
			$table->dropindex('estimation_pages_estimation_id_index');
		});
	}
}
