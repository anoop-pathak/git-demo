<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CrateOnboardChecklistTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('onboard_checklists', function(Blueprint $table)
		{
			$table->engine = 'InnoDB';
			$table->increments('id');
			$table->integer('section_id')->unsigned();
			$table->foreign('section_id')->references('id')->on('onboard_checklist_sections');
			$table->string('title');
			$table->text('action')->nullable();
			$table->string('video_url')->nullable();
			$table->boolean('is_required')->default(false);
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
		Schema::drop('onboard_checklists');
	}

}

