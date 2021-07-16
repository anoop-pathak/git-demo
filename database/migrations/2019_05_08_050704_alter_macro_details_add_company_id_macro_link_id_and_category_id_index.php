<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterMacroDetailsAddCompanyIdMacroLinkIdAndCategoryIdIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('macro_details', function(Blueprint $table) {
			if (!isIndexExists('macro_details', 'macro_details_company_id_index')) {
				$table->index('company_id');
			}
			if (!isIndexExists('macro_details', 'macro_details_macro_link_id_index')) {
				$table->index('macro_link_id');
			}
			if (!isIndexExists('macro_details', 'macro_details_category_id_index')) {
				$table->index('category_id');
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
		Schema::table('macro_details', function(Blueprint $table) {
			$table->dropIndex('macro_details_company_id_index');
			$table->dropIndex('macro_details_macro_link_id_index');
			$table->dropIndex('macro_details_category_id_index');
		});
	}

}
