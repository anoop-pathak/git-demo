<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableShinglesUnderlaymentsAddCompanyId extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('shingles_underlayments', function(Blueprint $table)
		{
			$table->integer('company_id')->after('type')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('shingles_underlayments', function(Blueprint $table)
		{
			$table->dropColumn('company_id');
		});
	}

}
