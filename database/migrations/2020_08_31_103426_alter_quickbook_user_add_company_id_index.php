<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterQuickbookUserAddCompanyIdIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('quickbooks_user', function(Blueprint $table)
		{
			if (!isIndexExists('quickbooks_user', 'quickbooks_user_company_id_index')) {
				
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
		Schema::table('quickbooks_user', function(Blueprint $table)
		{
			$table->dropindex('quickbooks_user_company_id_index');
		});
	}

}
