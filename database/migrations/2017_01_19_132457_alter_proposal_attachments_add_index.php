<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterProposalAttachmentsAddIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('proposal_attachments', function($table) 
		{
			if (!isIndexExists('proposal_attachments', 'proposal_attachments_proposal_id_index')) {
				
				$table->index('proposal_id');
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
		Schema::table('proposal_attachments', function($table) 
		{
			$table->dropindex('proposal_attachments_proposal_id_index');
		});
	}

}
