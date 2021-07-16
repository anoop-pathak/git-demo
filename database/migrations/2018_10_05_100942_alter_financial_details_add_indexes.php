<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterFinancialDetailsAddIndexes extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('financial_details', function(Blueprint $table)
		{
			$table->index('company_id');
			$table->index('job_id');
			$table->index('category_id');
			$table->index('worksheet_id');
			$table->index('product_id');
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
		Schema::table('financial_details', function(Blueprint $table)
		{
			$table->dropIndex('financial_details_company_id_index');
			$table->dropIndex('financial_details_job_id_index');
			$table->dropIndex('financial_details_category_id_index');
			$table->dropIndex('financial_details_worksheet_id_index');
			$table->dropIndex('financial_details_product_id_index');
			$table->dropIndex('financial_details_supplier_id_index');
		});
	}

}
