<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterCustomTaxesQuickbookTaxCodeIdColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('custom_taxes', function(Blueprint $table)
		{
			$table->integer('quickbook_tax_code_id')->after('tax_rate')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('custom_taxes', function(Blueprint $table)
		{
			$table->dropColumn('quickbook_tax_code_id');
		});
	}

}
