<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableEmailsAddBounceNotificationResponseColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('emails', function(Blueprint $table) {
			$table->mediumText('bounce_notification_response')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('emails', function(Blueprint $table) {
			$table->dropColumn('bounce_notification_response');
		});
	}

}
