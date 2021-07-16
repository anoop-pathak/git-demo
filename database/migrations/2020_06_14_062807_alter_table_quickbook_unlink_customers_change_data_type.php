<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableQuickbookUnlinkCustomersChangeDataType extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement('ALTER TABLE quickbook_unlink_customers MODIFY COLUMN quickbook_id VARCHAR(256) NULL');
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::statement('ALTER TABLE quickbook_unlink_customers MODIFY COLUMN quickbook_id INTEGER NULL');
	}

}
