<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterCompanyContactsAddIndexes extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		//
		Schema::table('company_contacts', function($table)
		{
			if (!isIndexExists('company_contacts', 'company_contacts_company_id_index')) {
				
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
		Schema::table('company_contacts', function($table)
		{
			$table->dropIndex('company_contacts_company_id_index');
		});
	}

}
