<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterEmailsAddIndexOnConversationId extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('emails', function($table)
		{
			if (!isIndexExists('emails','emails_conversation_id_index')) {
				
				$table->index('conversation_id');
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
		Schema::table('emails', function($table)
		{
			$table->dropIndex('emails_conversation_id_index');
		});
	}

}
