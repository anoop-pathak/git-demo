<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableDeletedQuickbookCreditsAddCreditIdAndQbCreditId extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('deleted_quickbook_credits', function(Blueprint $table) {
			$table->integer('qb_credit_id')->nullable();
			$table->integer('credit_id')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('deleted_quickbook_credits', function(Blueprint $table) {
			$table->dropColumn('qb_credit_id');
			$table->dropColumn('credit_id');
		});
	}

}
