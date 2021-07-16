<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterLaboursAddProfilePic extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('labours', function(Blueprint $table)
		{
			$table->text('profile_pic')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('labours', function(Blueprint $table)
		{
			$table->dropColumn('profile_pic');
		});
	}

}
