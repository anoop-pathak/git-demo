<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterHoverClientsAddWebhookVerifiedField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('hover_clients', function(Blueprint $table)
		{
			$table->boolean('webhook_verified')->default(0);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('hover_clients', function(Blueprint $table)
		{
			$table->dropColumn('webhook_verified');
		});
	}

}
