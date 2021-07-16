<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterUserDevicesTableAddPrimaryDeviceColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('user_devices', function(Blueprint $table) {
			$table->boolean('is_primary_device')->after('device_token')->default(false);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('user_devices',function(Blueprint $table){
			$table->dropColumn('is_primary_device');
		});
	}

}
