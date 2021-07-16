<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterFinancialAccountsAddLevelAndUpdatedBySoftDeleteFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('financial_accounts', function(Blueprint $table)
		{
			$table->integer('updated_by')->after('updated_at')->index();
			$table->integer('level')->after('parent_id');
			$table->softDeletes();
			$table->integer('deleted_by')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('financial_accounts', function(Blueprint $table)
		{
			$table->dropColumn('updated_by');
			$table->dropColumn('level');
			$table->dropColumn('deleted_at');
			$table->dropColumn('deleted_by');
		});
	}

}
