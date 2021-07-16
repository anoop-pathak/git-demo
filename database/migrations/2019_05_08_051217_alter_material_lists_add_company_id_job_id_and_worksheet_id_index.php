<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterMaterialListsAddCompanyIdJobIdAndWorksheetIdIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('material_lists', function(Blueprint $table) {
			if (!isIndexExists('material_lists', 'material_lists_company_id_index')) {
				$table->index('company_id');
			}
			if (!isIndexExists('material_lists', 'material_lists_job_id_index')) {
				$table->index('job_id');
			}
			if (!isIndexExists('material_lists', 'material_lists_worksheet_id_index')) {
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
		Schema::table('material_lists', function(Blueprint $table) {
			$table->dropIndex('material_lists_company_id_index');
			$table->dropIndex('material_lists_job_id_index');
			$table->dropIndex('material_lists_worksheet_id_index');
		});
	}

}
