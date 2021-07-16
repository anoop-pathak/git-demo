<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableUserProfileAddIndexOnCountryIdAndStateIdColumns extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('user_profile', function($table) {
			 $table->index('state_id');
			 $table->index('country_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('user_profile', function($table) {
			 $table->dropIndex(['country_id']);
			 $table->dropIndex(['state_id']);
		});
	}

}
