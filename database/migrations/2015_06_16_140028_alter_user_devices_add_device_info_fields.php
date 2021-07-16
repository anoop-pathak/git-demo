<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterUserDevicesAddDeviceInfoFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('user_devices',function($table){
			$table->integer('company_id');
			$table->string('app_version');
			$table->string('platform');
			$table->string('manufacturer');
			$table->string('os_version')->nullable();
			$table->string('model')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('user_devices',function($table){
			$table->dropColumn('company_id');
			$table->dropColumn('app_version');
			$table->dropColumn('platform');
			$table->dropColumn('os_version');
			$table->dropColumn('model');
			$table->dropColumn('manufacturer');
		});
	}

}
