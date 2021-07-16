<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOriginColumnJobCreditsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_credits', function(Blueprint $table)
		{
			$table->tinyInteger('origin')->default(0);
			$table->integer('quickbook_sync_token')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_credits', function(Blueprint $table)
		{
			$table->dropColumn('origin');
			$table->dropColumn('quickbook_sync_token');
		});
	}

}
