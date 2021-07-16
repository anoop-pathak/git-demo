<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDeletedQuickbookRefundsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('deleted_quickbook_refunds', function(Blueprint $table) {
			$table->increments('id');
			$table->integer('company_id')->index();
			$table->integer('customer_id')->index();
			$table->integer('job_id')->nullable();
			$table->integer('qb_customer_id')->index();
			$table->integer('qb_refund_id')->nullable();
			$table->integer('refund_id')->nullable();
			$table->text('object')->nullable();
			$table->text('data')->nullable();
			$table->integer('created_by')->nullable();
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
		Schema::dropIfExists('deleted_quickbook_refunds');
	}

}
