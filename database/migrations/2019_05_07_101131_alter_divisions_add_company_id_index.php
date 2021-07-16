<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterDivisionsAddCompanyIdIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('divisions', function(Blueprint $table) {
			if (!isIndexExists('divisions', 'divisions_company_id_index')) {
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
		Schema::table('divisions', function(Blueprint $table) {
			$table->dropIndex('divisions_company_id_index');
		});
	}

}
