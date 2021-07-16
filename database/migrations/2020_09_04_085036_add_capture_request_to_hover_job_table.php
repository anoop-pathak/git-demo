<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCaptureRequestToHoverJobTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('hover_jobs', function(Blueprint $table)
		{
			$table->boolean('is_capture_request')->after('hover_user_id')->nullable();
			$table->integer('capture_request_id')->after('is_capture_request')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('hover_jobs', function(Blueprint $table)
		{
			$table->dropColumn(array('is_capture_request', 'capture_request_id'));
		});
	}

}
