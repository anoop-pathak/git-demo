<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobInvoiceLinesAddMultiTiersFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_invoice_lines', function($table)
		{
			$table->string('tier1')->nullable()->after('invoice_id');
			$table->string('tier2')->nullable()->after('tier1');
			$table->string('tier3')->nullable()->after('tier2');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_invoice_lines', function($table)
		{
			$table->dropColumn('tier1');
			$table->dropColumn('tier2');
			$table->dropColumn('tier3');
		});
	}

}
