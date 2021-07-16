<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQuickbookWebhooksTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('quickbook_webhooks', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('request_id');
			$table->text('payload');
			$table->text('headers');
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
		Schema::drop('quickbook_webhooks');
	}

}
