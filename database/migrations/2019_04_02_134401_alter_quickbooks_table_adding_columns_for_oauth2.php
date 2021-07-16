<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterQuickbooksTableAddingColumnsForOauth2 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('quickbooks', function(Blueprint $table)
		{
			$table->string('token_type')->nullable()->after('quickbook_id');
			$table->string('expires_in')->nullable()->after('access_token');
			$table->string('refresh_token_expires_in')->nullable()->before('created_at');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('quickbooks', function(Blueprint $table)
		{
			$table->dropColumn('token_type');
			$table->dropColumn('expires_in');
			$table->dropColumn('refresh_token_expires_in');
		});
	}

}
