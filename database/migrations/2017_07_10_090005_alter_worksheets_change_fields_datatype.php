<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterWorksheetsChangeFieldsDatatype extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement('ALTER TABLE worksheets MODIFY COLUMN overhead VARCHAR(255) NULL');
		DB::statement('ALTER TABLE worksheets MODIFY COLUMN profit VARCHAR(255) NULL');
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::statement('ALTER TABLE worksheets MODIFY COLUMN overhead FLOAT NULL');
		DB::statement('ALTER TABLE worksheets MODIFY COLUMN profit FLOAT NULL');
	}

}
