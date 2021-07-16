<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterYoutubeVideoLinksAddForAllTradeField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('youtube_video_links', function(Blueprint $table) {
			$table->boolean('for_all_trades')->default(false);
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
			$table->dropColumn('for_all_trades');
		});
	}

}
