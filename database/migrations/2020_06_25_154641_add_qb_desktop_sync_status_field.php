<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddQbDesktopSyncStatusField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('customers', function(Blueprint $table) {
			$table->tinyInteger('qb_desktop_sync_status')->nullable()->default(null);
		});
		Schema::table('jobs', function(Blueprint $table) {
			$table->tinyInteger('qb_desktop_sync_status')->nullable()->default(null);
		});
		Schema::table('job_invoices', function(Blueprint $table) {
			$table->tinyInteger('qb_desktop_sync_status')->nullable()->default(null);
		});
		Schema::table('job_payments', function(Blueprint $table) {
			$table->tinyInteger('qb_desktop_sync_status')->nullable()->default(null);
		});
		Schema::table('job_credits', function(Blueprint $table) {
			$table->tinyInteger('qb_desktop_sync_status')->nullable()->default(null);
		});
		Schema::table('financial_accounts', function(Blueprint $table) {
			$table->tinyInteger('qb_desktop_sync_status')->nullable()->default(null);
		});
		Schema::table('vendors', function(Blueprint $table) {
			$table->tinyInteger('qb_desktop_sync_status')->nullable()->default(null);
		});
		Schema::table('job_refunds', function(Blueprint $table) {
			$table->tinyInteger('qb_desktop_sync_status')->nullable()->default(null);
		});
		Schema::table('vendor_bills', function(Blueprint $table) {
			$table->tinyInteger('qb_desktop_sync_status')->nullable()->default(null);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('customers', function(Blueprint $table) {
			$table->dropColumn('qb_desktop_sync_status');
		});
		Schema::table('jobs', function(Blueprint $table) {
			$table->dropColumn('qb_desktop_sync_status');
		});
		Schema::table('job_invoices', function(Blueprint $table) {
			$table->dropColumn('qb_desktop_sync_status');
		});
		Schema::table('job_payments', function(Blueprint $table) {
			$table->dropColumn('qb_desktop_sync_status');
		});
		Schema::table('job_credits', function(Blueprint $table) {
			$table->dropColumn('qb_desktop_sync_status');
		});
		Schema::table('financial_accounts', function(Blueprint $table) {
			$table->dropColumn('qb_desktop_sync_status');
		});
		Schema::table('vendors', function(Blueprint $table) {
			$table->dropColumn('qb_desktop_sync_status');
		});
		Schema::table('job_refunds', function(Blueprint $table) {
			$table->dropColumn('qb_desktop_sync_status');
		});
		Schema::table('vendor_bills', function(Blueprint $table) {
			$table->dropColumn('qb_desktop_sync_status');
		});
	}

}
