<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterCustomTaxesAddQbDesktopDeleteAndQbDesktopSequenceEditFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('custom_taxes', function(Blueprint $table)
		{
			$table->string('qb_desktop_sequence_number')->after('quickbook_tax_code_id')->nullable();
			$table->string('qb_desktop_id')->after('quickbook_tax_code_id')->nullable();
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
			$table->dropColumn('qb_desktop_id');
			$table->dropColumn('qb_desktop_sequence_number');
		});
	}

}
