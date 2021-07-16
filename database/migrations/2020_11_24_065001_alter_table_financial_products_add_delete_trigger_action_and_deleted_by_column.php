<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableFinancialProductsAddDeleteTriggerActionAndDeletedByColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('financial_products', function(Blueprint $table) {
			$table->integer('deleted_by')->nullable()->after('deleted_at');
			$table->string('delete_trigger_action')->nullable()->after('deleted_by');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('financial_products', function(Blueprint $table) {
			$table->dropColumn('delete_trigger_action');
			$table->dropColumn('deleted_by');
		});
	}

}
