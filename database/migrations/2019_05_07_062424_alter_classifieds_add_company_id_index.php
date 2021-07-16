<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterClassifiedsAddCompanyIdIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('classifieds', function(Blueprint $table) {
			if (!isIndexExists('classifieds', 'classifieds_company_id_index')) {
				$table->index('company_id');
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
		Schema::table('classifieds', function(Blueprint $table) {
			$table->dropIndex('classifieds_company_id_index');
		});
	}

}
