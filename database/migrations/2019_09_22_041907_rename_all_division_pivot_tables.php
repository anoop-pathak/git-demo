<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenameAllDivisionPivotTables extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::rename('user_divisions', 'user_division');
		Schema::rename('macro_divisions', 'macro_division');
		Schema::rename('template_divisions', 'template_division');
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::rename('user_division', 'user_divisions');
		Schema::rename('macro_division', 'macro_divisions');
		Schema::rename('template_division', 'template_divisions');
	}

}
