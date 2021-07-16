<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStateToHoverCaptureRequestsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('hover_capture_requests', function(Blueprint $table)
		{
			$table->string('state')->after('deliverable_id')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('hover_capture_requests', function(Blueprint $table)
		{
			$table->dropColumn('state');
		});
	}

}
