<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobInvoiceLinesAddIsChargeableColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_invoice_lines', function(Blueprint $table) {
			$table->boolean('is_chargeable')->default(true);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_invoice_lines', function(Blueprint $table) {
			$table->dropColumn('is_chargeable');
		});
	}

}
