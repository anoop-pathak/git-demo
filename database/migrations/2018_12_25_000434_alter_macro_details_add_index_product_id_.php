<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterMacroDetailsAddIndexProductId extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('macro_details', function($table) {
			if (!isIndexExists('macro_details', 'macro_details_product_id_index')) {
					
				$table->index('product_id');
			}

			// if (!isIndexExists('macro_details', 'macro_details_macro_id_index')) {
				
			// 	$table->index('macro_id');
			// }
		});

	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('macro_details', function($table) {
			$table->dropIndex('product_id');
			// $table->dropIndex('macro_id');
		});
	}

}
