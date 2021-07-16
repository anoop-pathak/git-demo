<?php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
class AlterJobsAddHoverUserEmailField extends Migration {
 	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
 	public function up()
 	{
 		Schema::table('jobs', function(Blueprint $table)
 		{
 			$table->string('hover_user_email')->nullable();
 		});
 	}
 	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
 	public function down()
 	{
 		Schema::table('jobs', function(Blueprint $table)
 		{
 			$table->dropColumn('hover_user_email');
 		});
 	}
 }