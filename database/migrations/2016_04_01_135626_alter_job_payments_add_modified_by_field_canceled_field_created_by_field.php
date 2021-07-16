<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobPaymentsAddModifiedByFieldCanceledFieldCreatedByField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_payments', function(Blueprint $table)
		{
			$table->engine = 'InnoDB';
			$table->integer('created_by');
			$table->integer('modified_by');
			$table->string('quickbook_id')->nullable();
			$table->integer('quickbook_sync_token')->nullable();
			$table->timestamp('canceled')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_payments', function(Blueprint $table)
		{
			$table->dropColumn('created_by');
			$table->dropColumn('modified_by');
			$table->dropColumn('canceled');
			$table->dropColumn('quickbook_id');
			$table->dropColumn('quickbook_sync_token');
		});
	}

}
