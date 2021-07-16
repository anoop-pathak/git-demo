<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTempProposalPagesAddAutoFillRequiredField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('temp_proposal_pages', function(Blueprint $table)
		{
			$table->text('auto_fill_required')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('temp_proposal_pages', function(Blueprint $table)
		{
			$table->dropColumn('auto_fill_required');
		});
	}

}
