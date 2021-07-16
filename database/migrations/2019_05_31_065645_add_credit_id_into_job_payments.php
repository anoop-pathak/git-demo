<?php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
class AddCreditIdIntoJobPayments extends Migration {
 	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
 	public function up()
 	{
 		Schema::table('job_payments', function(Blueprint $table) {
 			$table->integer('credit_id')->after('customer_id')->nullable();
 		});
 	}
 	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
 	public function down()
 	{
 		Schema::table('job_payments', function(Blueprint $table) {
 			$table->dropColumn('credit_id');
 		});
 	}
 }