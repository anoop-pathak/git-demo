<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableTempProposalPagesAddPageTypeColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('temp_proposal_pages', function(Blueprint $table) {
			$table->string('page_type')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('temp_proposal_pages', function(Blueprint $table) {
			$table->dropColumn('page_type');
		});
	}

}
