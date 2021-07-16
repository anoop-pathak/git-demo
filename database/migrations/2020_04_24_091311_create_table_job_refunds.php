<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableJobRefunds extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('job_refunds', function(Blueprint $table)
		{
			$table->engine = 'InnoDB';
			$table->increments('id');
			$table->integer('company_id')->index();
			$table->integer('customer_id')->index();
			$table->integer('job_id')->index();
			$table->integer('financial_account_id')->index();
			$table->string('payment_method')->nullable();
			$table->string('refund_number')->nullable();
			$table->date('refund_date')->nullable();
			$table->string('address')->nullable();
			$table->string('file_path')->nullable();
			$table->float('total_amount', 16, 2)->nullable();
			$table->float('tax_amount', 16, 2)->nullable();
			$table->integer('created_by')->nullable();
			$table->integer('updated_by')->nullable();
			$table->integer('canceled_by')->nullable();
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
		Schema::drop('job_refunds');
	}

}
