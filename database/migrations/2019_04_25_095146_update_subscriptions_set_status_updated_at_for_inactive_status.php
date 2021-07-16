<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateSubscriptionsSetStatusUpdatedAtForInactiveStatus extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement(
			"UPDATE subscriptions 
			SET status_updated_at = created_at 
			WHERE status = 'inactive' AND status_updated_at is null"
		);
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		
	}

}
