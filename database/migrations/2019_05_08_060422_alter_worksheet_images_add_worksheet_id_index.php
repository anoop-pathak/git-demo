<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterWorksheetImagesAddWorksheetIdIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('worksheet_images', function(Blueprint $table) {
			if (!isIndexExists('worksheet_images', 'worksheet_images_worksheet_id_index')) {
				$table->index('worksheet_id');
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
		Schema::table('worksheet_images', function(Blueprint $table) {
			$table->dropIndex('worksheet_images_worksheet_id_index');
		});
	}

}
