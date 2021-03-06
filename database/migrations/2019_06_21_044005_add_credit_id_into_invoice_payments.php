<?php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
class AddCreditIdIntoInvoicePayments extends Migration {
 	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
 	public function up()
 	{
 		Schema::table('invoice_payments', function(Blueprint $table) {
 			$table->integer('credit_id')->after('invoice_id')->nullable();
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
 			$table->dropColumn('credit_id');
 		});
 	}
 }