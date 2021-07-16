<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTitleToYoutubeVideoLinks extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('youtube_video_links', function(Blueprint $table) {
			$table->string('title')->after('company_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('youtube_video_links', function(Blueprint $table) {
			$table->dropColumn('title');
		});
	}

}
