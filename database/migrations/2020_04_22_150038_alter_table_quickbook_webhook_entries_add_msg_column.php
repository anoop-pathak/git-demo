<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableQuickbookWebhookEntriesAddMsgColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('quickbook_webhook_entries', function(Blueprint $table) {
			$table->string('msg')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('quickbook_webhook_entries', function(Blueprint $table) {
			$table->dropColumn('msg');
		});
	}

}
