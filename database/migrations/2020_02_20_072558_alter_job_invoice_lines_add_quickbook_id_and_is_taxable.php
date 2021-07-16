<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobInvoiceLinesAddQuickbookIdAndIsTaxable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_invoice_lines', function(Blueprint $table) 
		{
			$table->integer('quickbook_id')->nullable();
			$table->tinyInteger('is_taxable')->default(1); // default taxable
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_invoice_lines', function(Blueprint $table) 
		{
			$table->dropColumn('quickbook_id');
			$table->dropColumn('is_taxable');
		});
	}

}
