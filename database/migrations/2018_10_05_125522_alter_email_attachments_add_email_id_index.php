<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterEmailAttachmentsAddEmailIdIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('email_attachments', function(Blueprint $table)
		{
			$table->index('email_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('email_attachments', function(Blueprint $table)
		{
			$table->dropIndex('email_attachments_email_id_index');
		});
	}

}
