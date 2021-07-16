<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenameQuickbookIdAndQuickbookParentIdField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('qbo_customers', function(Blueprint $table)
		{
			$table->renameColumn('quickbook_id', 'qb_id');
			$table->renameColumn('quickbook_parent_id', 'qb_parent_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('qbo_customers', function(Blueprint $table)
		{
			$table->renameColumn('qb_id', 'quickbook_id');
			$table->renameColumn('qb_parent_id', 'quickbook_parent_id');
		});
	}

}
