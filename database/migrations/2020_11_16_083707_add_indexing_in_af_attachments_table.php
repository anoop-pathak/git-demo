<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexingInAfAttachmentsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('af_attachments', function(Blueprint $table)
		{
			$table->index('af_id');
			$table->index('parent_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('af_attachments', function(Blueprint $table)
		{
			$table->dropIndex('af_attachments_af_id_index');
			$table->dropIndex('af_attachments_parent_id_index');
		});
	}

}
