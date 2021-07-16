<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterNewResourcesAddReferenceIdIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('new_resources', function(Blueprint $table)
		{
			if (!isIndexExists('new_resources','new_resources_reference_id_index')) {
				
				$table->index('reference_id');
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
		Schema::table('new_resources', function(Blueprint $table)
		{
			$table->dropIndex('new_resources_reference_id_index');
		});
	}

}
