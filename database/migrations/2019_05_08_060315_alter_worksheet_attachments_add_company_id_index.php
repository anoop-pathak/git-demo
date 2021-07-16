<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterWorksheetAttachmentsAddCompanyIdIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('worksheet_attachments', function(Blueprint $table) {
			if (!isIndexExists('worksheet_attachments', 'worksheet_attachments_company_id_index')) {
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
		Schema::table('worksheet_attachments', function(Blueprint $table) {
			$table->dropIndex('worksheet_attachments_company_id_index');
		});
	}

}
