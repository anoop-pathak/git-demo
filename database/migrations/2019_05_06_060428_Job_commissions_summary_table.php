<?php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
class JobCommissionsSummaryTable extends Migration {
 	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
 	public function up()
 	{
 		Schema::create('job_commission_payments', function(Blueprint $table)
 		{
 			$table->increments('id');
 			$table->integer('company_id')->index();
 			$table->integer('job_id')->index();
 			$table->integer('commission_id')->unsigned();
 			$table->foreign('commission_id')->references('id')->on('job_commissions');
 			$table->integer('paid_by');
 			$table->decimal('amount');
 			$table->date('paid_on');
 			$table->dateTime('canceled_at')->nullable();
 			$table->timestamps();
 		});
 	}
 	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
 	public function down()
 	{
 		Schema::drop('job_commission_payments');
 	}
}