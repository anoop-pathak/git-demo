<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterClassifiedImagesAddClassifiedIdIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('classified_images', function(Blueprint $table) {
			if (!isIndexExists('classified_images', 'classified_images_classified_id_index')) {
				$table->index('classified_id');
			}
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('classified_images', function(Blueprint $table) {
			$table->dropIndex('classified_images_classified_id_index');
		});
	}

}
