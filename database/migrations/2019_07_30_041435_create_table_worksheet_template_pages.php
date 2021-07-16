<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableWorksheetTemplatePages extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('worksheet_template_pages', function(Blueprint $table) {
			$table->increments('id');
			$table->integer('worksheet_id')->index();
			$table->integer('company_id')->index();
			$table->mediumText('content');
			$table->text('auto_fill_required')->nullable();
			$table->string('page_type');
			$table->string('title')->nullable();
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('worksheet_template_pages');
	}

}
