<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableWorksheetTemplatePagesChangePageTypeColumnType extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement('ALTER TABLE `worksheet_template_pages` MODIFY `page_type` VARCHAR(255) NULL;');
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::statement('ALTER TABLE `worksheet_template_pages` MODIFY `page_type` VARCHAR(255) NOT NULL;');
	}

}
