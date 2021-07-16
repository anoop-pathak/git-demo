<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableJobRefundsAddQuickbookId extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_refunds', function(Blueprint $table) {
			$table->integer('quickbook_id')->nullable();
			$table->integer('quickbook_sync_token')->nullable();
			$table->tinyInteger('quickbook_sync_status')->nullable()->default(null);
			$table->tinyInteger('origin')->default(0);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_refunds', function(Blueprint $table) {
			$table->dropColumn('quickbook_id');
			$table->dropColumn('quickbook_sync_token');
			$table->dropColumn('quickbook_sync_status');
			$table->dropColumn('origin');
		});
	}

}
