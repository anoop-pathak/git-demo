<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterQuickbookSyncCustomersDropExtraColumns extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('quickbook_sync_customers', function(Blueprint $table)
		{
			$table->dropColumn('is_duplicate');
			$table->dropColumn('is_valid');
			$table->dropColumn('object_id');
			$table->dropColumn('meta');
			$table->dropColumn('errors');
			$table->dropColumn('status');
			$table->dropColumn('action');
			$table->dropColumn('msg');
			$table->dropColumn('matching_customer');
			$table->dropColumn('first_name');
			$table->dropColumn('last_name');
			$table->dropColumn('email');
			$table->dropColumn('additional_emails');
			$table->dropColumn('phones');
			$table->dropColumn('sync_request_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('quickbook_sync_customers', function(Blueprint $table)
		{
			$table->boolean('is_duplicate')->default(false);
			$table->boolean('is_valid')->default(false);
			$table->integer('object_id')->nullable();
			$table->text('meta')->nullable();
			$table->text('errors')->nullable();
			$table->string('status', 100)->nullable();
			$table->string('action', 100)->nullable();
			$table->text('msg')->nullable();
			$table->string('matching_customer')->nullable();
			$table->string('first_name');
			$table->string('last_name');
			$table->string('email')->nullable();
			$table->text('additional_emails')->nullable();
			$table->text('phones')->nullable();
			$table->integer('sync_request_id')->nullable();
			$table->timestamps();
		});
	}

}
