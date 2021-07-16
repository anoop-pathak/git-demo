<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterActivityLogMetaAddIndexOnActivityId extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('activity_log_meta', function($table)
		{
			if (!isIndexExists('activity_log_meta', 'activity_log_meta_activity_id_index')) {
				
				$table->index('activity_id');
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
		Schema::table('activity_log_meta', function($table) {
			$table->dropIndex('activity_log_meta_activity_id_index');
		});
	}

}
