<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobInvoiceLinesAddQuantityField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_invoice_lines', function(Blueprint $table)
		{
			$table->string('quantity', 7)->default(1);
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
			$table->dropColumn('quantity');
		});
	}

}
