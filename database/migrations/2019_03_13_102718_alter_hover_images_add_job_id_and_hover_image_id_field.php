<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterHoverImagesAddJobIdAndHoverImageIdField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('hover_images', function(Blueprint $table)
		{
			$table->integer('job_id');
			$table->integer('hover_image_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('hover_images', function(Blueprint $table)
		{
			$table->dropColumn('job_id');
			$table->dropColumn('hover_image_id');
		});
	}

}
