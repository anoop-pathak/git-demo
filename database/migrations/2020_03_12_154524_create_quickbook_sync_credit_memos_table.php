<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQuickbookSyncCreditMemosTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('quickbook_sync_credit_memos', function($table) {
			$table->increments('id');
			$table->integer('company_id');
			$table->integer('job_progress_customer_id')->nullable();
			$table->integer('quickbook_customer_id')->nullable();
			$table->integer('object_id')->nullable();
			$table->decimal('amount', 16, 2)->nullable();
			$table->decimal('balance', 16, 2)->nullable();
			$table->text('meta')->nullable();
			$table->text('errors')->nullable();
			$table->string('status', 100)->nullable();
			$table->string('action', 100)->nullable();
			$table->text('msg')->nullable();
			$table->integer('created_by')->nullable();
			$table->integer('last_modified_by')->nullable();
			$table->timestamps();

			$table->index('company_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('quickbook_sync_credit_memos');
	}

}
