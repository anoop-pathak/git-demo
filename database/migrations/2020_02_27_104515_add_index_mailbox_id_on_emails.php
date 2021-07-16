<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexMailboxIdOnEmails extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('emails', function(Blueprint $table)
		{
			if (!isIndexExists('emails', 'emails_mailbox_id_index')) {
				
				$table->index('mailbox_id');
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
		Schema::table('emails', function(Blueprint $table)
		{
			$table->dropIndex('emails_mailbox_id_index');
		});
	}

}
