<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterProposalsPagesAddIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('proposal_pages', function($table) 
		{
			if (!isIndexExists('proposal_pages', 'proposal_pages_proposal_id_index')) {
				
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
		Schema::table('proposal_pages', function($table) 
		{
			$table->dropindex('proposal_pages_proposal_id_index');
		});
	}

}
