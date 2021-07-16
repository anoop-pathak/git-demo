<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablePhoneMessagesChangeCompanyIdNullable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		// DB::statement('ALTER TABLE `phone_messages` CHANGE COLUMN `company_id` `company_id` INT(11) NULL DEFAULT NULL');
		// DB::statement('ALTER TABLE `phone_messages` CHANGE COLUMN `send_by` `send_by` INT(11) NULL DEFAULT NULL');
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		// DB::statement('ALTER TABLE `phone_messages` CHANGE COLUMN `company_id` `company_id` INT(11) NOT NULL');
		// DB::statement('ALTER TABLE `phone_messages` CHANGE COLUMN `send_by` `send_by` INT(11) NOT NULL');
	}

}
