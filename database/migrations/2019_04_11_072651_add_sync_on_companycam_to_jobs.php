<?php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
class AddSyncOnCompanycamToJobs extends Migration {
 	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
 	public function up()
 	{
 		Schema::table('jobs', function(Blueprint $table) {
 			$table->boolean('sync_on_companycam')->default(false);
 		});
 	}
 	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
 	public function down()
 	{
 		Schema::table('jobs', function(Blueprint $table) {
 			$table->dropColumn('sync_on_companycam');
 		});
 	}
 }