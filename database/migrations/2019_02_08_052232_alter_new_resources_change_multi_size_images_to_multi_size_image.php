<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterNewResourcesChangeMultiSizeImagesToMultiSizeImage extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement("ALTER TABLE new_resources CHANGE multi_size_images multi_size_image boolean DEFAULT false");
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::statement("ALTER TABLE new_resources CHANGE multi_size_image multi_size_images boolean");
	}

}
