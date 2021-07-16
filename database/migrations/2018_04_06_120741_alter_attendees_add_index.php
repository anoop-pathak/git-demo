<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterAttendeesAddIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		//
		Schema::table('attendees', function($table)
		{
			if (!isIndexExists('attendees', 'attendees_appointment_id_index')) {
				
				$table->index('appointment_id');
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
		Schema::table('attendees', function($table)
		{
			$table->dropIndex('attendees_appointment_id_index');
		});
	}

}
