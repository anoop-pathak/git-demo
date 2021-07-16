<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterFinancialProductsAddBatchIdDeletedAtField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('financial_products', function(Blueprint $table)
		{
			$table->string('batch_id', 100)->nullable();
			$table->softDeletes();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('financial_products', function(Blueprint $table)
		{
			$table->dropColumn('batch_id');
			$table->dropColumn('deleted_at');
		});
	}

}
