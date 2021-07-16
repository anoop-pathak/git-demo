<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableYoutubeVideoLinkTrades extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('youtube_video_link_trades', function(Blueprint $table) {
			$table->increments('id');
			$table->integer('trade_id');
			$table->integer('youtube_video_link_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('youtube_video_link_trades');
	}

}
