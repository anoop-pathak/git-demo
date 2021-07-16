<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterSupplierProductsHistroyAddCompanyIdBatchIdSupplierIdIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('supplier_products_history', function(Blueprint $table)
		{
			$table->index('company_id');;
			$table->index('batch_id');
			$table->index('supplier_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('supplier_products_history', function(Blueprint $table)
		{
			$table->dropIndex('supplier_products_history_company_id_index');
			$table->dropIndex('supplier_products_history_batch_id_index');
			$table->dropIndex('supplier_products_history_supplier_id_index');
		});
	}

}
