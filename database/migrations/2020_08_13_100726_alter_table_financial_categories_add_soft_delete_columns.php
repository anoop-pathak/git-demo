<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableFinancialCategoriesAddSoftDeleteColumns extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('financial_categories', function(Blueprint $table) {
			$table->softDeletes();
			$table->integer('deleted_by')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('financial_categories', function(Blueprint $table) {
			$table->dropColumn('deleted_by');
			$table->dropColumn('deleted_at');
		});
	}

}
