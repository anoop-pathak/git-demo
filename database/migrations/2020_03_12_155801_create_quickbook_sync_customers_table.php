<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQuickbookSyncCustomersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('quickbook_sync_customers', function($table) {
			$table->increments('id');
			$table->integer('company_id');
			$table->boolean('is_duplicate')->default(false);
			$table->boolean('is_valid')->default(false);
			$table->integer('object_id')->nullable();
			$table->text('meta')->nullable();
			$table->text('errors')->nullable();
			$table->string('status', 100)->nullable();
			$table->string('action', 100)->nullable();
			$table->text('msg')->nullable();
			$table->integer('created_by')->nullable();
			$table->integer('last_modified_by')->nullable();
			$table->string('matching_customer')->nullable();
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
		Schema::dropIfExists('quickbook_sync_customers');
	}

}
