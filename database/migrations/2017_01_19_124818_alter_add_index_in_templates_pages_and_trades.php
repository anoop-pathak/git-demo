<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterAddIndexInTemplatesPagesAndTrades extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('template_pages', function($table) 
		{
			if (!isIndexExists('template_pages', 'template_pages_template_id_index')) {
				
				$table->index('template_id');
			}
		});

		Schema::table('template_trade', function($table) 
		{
			if (!isIndexExists('template_trade', 'template_trade_template_id_index')) {
				
				$table->index('template_id');
			}

			if (!isIndexExists('template_trade', 'template_trade_trade_id_index')) {
				
				$table->index('trade_id');
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
		Schema::table('template_pages', function($table) 
		{
			$table->dropindex('template_pages_template_id_index');
		});

		Schema::table('template_trade', function($table) 
		{
			$table->dropindex('template_trade_template_id_index');
			$table->dropindex('template_trade_trade_id_index');
		});
	}

}
