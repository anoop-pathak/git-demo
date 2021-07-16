<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddQbInvoicePathToJobInvoices extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_invoices', function(Blueprint $table)
		{
			$table->string('qb_file_path')->nullable();
			$table->string('qb_file_size')->nullable();
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
			$table->dropColumn('qb_file_path');
			$table->dropColumn('qb_file_size');
		});
	}

}
