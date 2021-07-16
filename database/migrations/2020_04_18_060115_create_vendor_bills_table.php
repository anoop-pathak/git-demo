<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVendorBillsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('vendor_bills', function(Blueprint $table)
		{
			$table->engine = 'InnoDB';
			$table->increments('id');
			$table->integer('company_id')->index();
			$table->integer('job_id')->index();
			$table->integer('customer_id')->index();
			$table->integer('vendor_id')->index();
			$table->date('bill_date');
			$table->date('due_date')->nullable();
			$table->string('file_path')->nullable();
			$table->string('bill_number')->nullable();
			$table->integer('bill_address_id')->nullable();
			$table->mediumText('note')->nullable();
			$table->float('total_amount')->nullable();
			$table->integer('created_by');
			$table->integer('updated_by');
			$table->integer('deleted_by')->nullable();
			$table->softDeletes();
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
		Schema::drop('vendor_bills');
	}

}
