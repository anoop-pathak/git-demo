<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterEmailReceipientAddIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		//
		Schema::table('email_recipient', function($table)
		{
			if (!isIndexExists('email_recipient', 'email_recipient_email_id_index')) {
				
				$table->index('email_id');
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
		Schema::table('email_recipient', function($table)
		{
			$table->dropIndex('email_recipient_email_id_index');
		});
	}

}
