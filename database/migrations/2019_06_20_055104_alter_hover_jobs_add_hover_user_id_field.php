<?php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
class AlterHoverJobsAddHoverUserIdField extends Migration {
 	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
 	public function up()
 	{
 		Schema::table('hover_jobs', function(Blueprint $table)
 		{
 			$table->integer('hover_user_id')->nullable();
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
 			$table->dropColumn('hover_user_id');
 		});
 	}
 }