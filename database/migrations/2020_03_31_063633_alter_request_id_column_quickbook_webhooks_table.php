<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterRequestIdColumnQuickbookWebhooksTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement('ALTER TABLE quickbook_webhooks MODIFY COLUMN request_id VARCHAR(256) NULL');
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::statement('ALTER TABLE quickbook_webhooks MODIFY COLUMN request_id INTEGER NULL');
	}

}
