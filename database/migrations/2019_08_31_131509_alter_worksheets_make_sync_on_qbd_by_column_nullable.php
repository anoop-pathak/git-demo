<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterWorksheetsMakeSyncOnQbdByColumnNullable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		 DB::statement('ALTER TABLE `worksheets` MODIFY `sync_on_qbd_by` INT  NULL;');
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::statement('ALTER TABLE `worksheets` MODIFY `sync_on_qbd_by` INT  NOT NULL;');
	}

}
