<?php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
class AddIndexIntoInvoicePayments extends Migration {
 	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
 	public function up()
 	{
 		Schema::table('invoice_payments', function(Blueprint $table) {
 			$table->index('credit_id');
 		});
 	}
 	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
 	public function down()
 	{
 		Schema::table('invoice_payments', function(Blueprint $table) {
 			$table->dropIndex(['credit_id']);
 		});
 	}
 }