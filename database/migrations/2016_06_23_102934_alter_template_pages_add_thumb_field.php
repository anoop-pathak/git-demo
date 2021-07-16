<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTemplatePagesAddThumbField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('template_pages', function($table){
			$table->renameColumn('thumb', 'image');
		});

		Schema::table('template_pages', function($table){
			$table->string('thumb')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('template_pages', function($table){
			$table->dropColumn('thumb');
		});

		Schema::table('template_pages', function($table){
			$table->renameColumn('image', 'thumb');
		});
	}
}
