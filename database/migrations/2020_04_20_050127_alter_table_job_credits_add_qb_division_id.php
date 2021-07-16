<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableJobCreditsAddQbDivisionId extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_credits', function(Blueprint $table) {
			$table->integer('qb_division_id')->nullable()->index();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_credits', function(Blueprint $table) {
			$table->dropColumn('qb_division_id');
		});
	}

}
