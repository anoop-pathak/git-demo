<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterAccountManagersAddManagingStateField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{	
		Schema::table('account_managers', function($table){
			$table->renameColumn('state_id', 'managing_state_id');
		});
		Schema::table('account_managers', function($table){
			$table->integer('state_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('account_managers', function($table){
			$table->dropColumn('state_id');
		});
		Schema::table('account_managers', function($table){
			$table->renameColumn('managing_state_id', 'state_id');
		});
	}

}
