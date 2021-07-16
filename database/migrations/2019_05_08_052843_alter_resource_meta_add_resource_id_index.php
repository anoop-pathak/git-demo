<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterResourceMetaAddResourceIdIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('resource_meta', function(Blueprint $table) {
			if (!isIndexExists('resource_meta', 'resource_meta_resource_id_index')) {
				$table->index('resource_id');
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
		Schema::table('resource_meta', function(Blueprint $table) {
			$table->dropIndex('resource_meta_resource_id_index');
		});
	}

}
