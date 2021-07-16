<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobInvoicesAddSoftDeleteFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_invoices', function(Blueprint $table)
		{
			$table->integer('deleted_by')->nullable();
   			$table->softDeletes();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_invoices', function(Blueprint $table)
		{
			$table->dropColumn('deleted_at');
			$table->dropColumn('deleted_by');
		});
	}

}
