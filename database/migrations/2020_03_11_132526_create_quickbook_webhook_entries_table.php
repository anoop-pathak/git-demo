<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQuickbookWebhookEntriesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('quickbook_webhook_entries', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('quickbook_webhook_id');
			$table->string('realm_id');
			$table->string('object_type');
			$table->integer('object_id');
			$table->string('operation');
			$table->dateTime('object_updated_at')->nullable();
			$table->tinyInteger('status')->nullable()->default(0);
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('quickbook_webhook_entries');
	}

}
