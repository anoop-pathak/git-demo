<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterUsersAddMissingIndexForCompanyIdAndGroupIdUsers extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('users', function($table) 
		{
			if (!isIndexExists('users', 'users_company_id_index')) {
				
				$table->index('company_id');
			}

			if (!isIndexExists('users', 'users_group_id_index')) {
				
				$table->index('group_id');
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
		Schema::table('users', function($table) 
		{
			$table->dropindex('users_company_id_index');
			$table->dropindex('users_group_id_index');
		});
	}

}
