<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterProposalsAddWorksheetIdIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('proposals', function(Blueprint $table) {
			if (!isIndexExists('proposals', 'proposals_worksheet_id_index')) {
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
		Schema::table('proposals', function(Blueprint $table) {
			$table->dropIndex('proposals_worksheet_id_index');
		});
	}

}
