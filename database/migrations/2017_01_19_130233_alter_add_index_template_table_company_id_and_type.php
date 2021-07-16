<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterAddIndexTemplateTableCompanyIdAndType extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('templates', function($table) 
		{
			if (!isIndexExists('templates', 'templates_company_id_index')) {
				
				$table->index('company_id');
			}

			if (!isIndexExists('templates', 'templates_type_index')) {
				
				$table->index('type');
			}

			if (!isIndexExists('templates', 'templates_for_all_trades_index')) {
				
				$table->index('for_all_trades');
			}

			if (!isIndexExists('templates', 'templates_page_type_index')) {
				
				$table->index('page_type');
			}

			if (!isIndexExists('templates', 'templates_insurance_estimate_index')) {
				
				$table->index('insurance_estimate');
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
		Schema::table('templates', function($table) 
		{
			$table->dropindex('templates_company_id_index');
			$table->dropindex('templates_type_index');
			$table->dropindex('templates_for_all_trades_index');
			$table->dropindex('templates_page_type_index');
			$table->dropindex('templates_insurance_estimate_index');
		});
	}

}
