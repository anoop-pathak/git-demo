<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateJobInvoicesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		// create job_invoices table
		Schema::create('job_invoices', function(Blueprint $table)
		{
			$table->engine = 'InnoDB';
			$table->increments('id');
			$table->string('file_path')->nullable();
			$table->string('quickbook_invoice_id')->nullable();
			$table->integer('quickbook_sync_token')->nullable();
			$table->timestamps();
		});
		DB::update("ALTER TABLE job_invoices AUTO_INCREMENT = 1000;");

		// delete quickbooks invoice fields from job and invoice_id
		Schema::table('jobs', function($table)
		{
			$table->integer('invoice_id')->nullable();
			$table->dropColumn('quickbook_invoice_id');
			$table->dropColumn('quickbook_sync_token');
		});

		// delete quickbooks invoice fields from change_orders and invoice_id
		Schema::table('change_orders', function($table)
		{
			$table->integer('invoice_id')->nullable();
			$table->dropColumn('quickbook_invoice_id');
			$table->dropColumn('quickbook_sync_token');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		// drop job_invoices table
		Schema::drop('job_invoices');

		Schema::table('jobs', function(Blueprint $table)
		{
			$table->dropColumn('invoice_id');
			$table->string('quickbook_invoice_id')->nullable();
			$table->integer('quickbook_sync_token')->nullable();
		});

		Schema::table('change_orders', function(Blueprint $table)
		{
			$table->dropColumn('invoice_id');
			$table->string('quickbook_invoice_id')->nullable();
			$table->integer('quickbook_sync_token')->nullable();
		});
	}

}
