<?php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
class AlterJobsChangeHoverUserEmailField extends Migration {
 	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
 	public function up()
 	{
 		DB::statement('ALTER TABLE jobs CHANGE hover_user_email hover_user_id varchar(250)');
 	}
 	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
 	public function down()
 	{
 		DB::statement('ALTER TABLE jobs CHANGE hover_user_id hover_user_email varchar(250)');
 	}
 }
