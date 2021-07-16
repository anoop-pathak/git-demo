<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterFinancialProductsAddIndexes extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('financial_products', function(Blueprint $table) {
			if (isIndexExists('financial_products', 'idx_financial_products_company_id')) {
				$table->dropIndex('idx_financial_products_company_id');
			}
			if (isIndexExists('financial_products', 'idx_financial_products_category_id')) {
				$table->dropIndex('idx_financial_products_category_id');
			}
			if (isIndexExists('financial_products', 'idx_financial_products_supplier_id')) {
				$table->dropIndex('idx_financial_products_supplier_id');
			}
			if (isIndexExists('financial_products', 'idx_financial_products_branch_code')) {
				$table->dropIndex('idx_financial_products_branch_code');
			}
			if (isIndexExists('financial_products', 'idx_financial_products_branch')) {
				$table->dropIndex('idx_financial_products_branch');
			}
			if (isIndexExists('financial_products', 'idx_financial_products_code')) {
				$table->dropIndex('idx_financial_products_code');
			}
			
			if (!isIndexExists('financial_products', 'financial_products_company_id_index')) {
				$table->index('company_id');
			}
			if (!isIndexExists('financial_products', 'financial_products_category_id_index')) {
				$table->index('category_id');
			}
			if (!isIndexExists('financial_products', 'financial_products_supplier_id_index')) {
				$table->index('supplier_id');
			}
			if (!isIndexExists('financial_products', 'financial_products_name_index')) {
				$table->index('name');
			}
			if (!isIndexExists('financial_products', 'financial_products_code_index')) {
				$table->index('code');
			}
			if (!isIndexExists('financial_products', 'financial_products_branch_code_index')) {
				$table->index('branch_code');
			}
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
			$table->dropIndex('financial_products_company_id_index');
			$table->dropIndex('financial_products_category_id_index');
			$table->dropIndex('financial_products_supplier_id_index');
			$table->dropIndex('financial_products_name_index');
			$table->dropIndex('financial_products_code_index');
			$table->dropIndex('financial_products_branch_code_index');
		});
	}

}
