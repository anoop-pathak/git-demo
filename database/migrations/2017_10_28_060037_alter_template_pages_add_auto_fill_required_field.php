<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTemplatePagesAddAutoFillRequiredField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('template_pages', function(Blueprint $table)
		{
			$table->text('auto_fill_required')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('template_pages', function(Blueprint $table)
		{
			$table->dropColumn('auto_fill_required');
		});
	}

}
