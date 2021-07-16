<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTimelogsAddLatLongFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('timelogs', function(Blueprint $table)
		{
			$table->float('lat', 10, 6)->nullable();
   		    $table->float('long', 10, 6)->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('timelogs', function(Blueprint $table)
		{
			$table->dropColumn('lat');
			$table->dropColumn('long');
		});
	}

}
