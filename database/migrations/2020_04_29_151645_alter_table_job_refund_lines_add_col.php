<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableJobRefundLinesAddCol extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_refund_lines', function(Blueprint $table){
			$table->integer('trade_id')->after('description')->nullable()->index();
			$table->integer('work_type_id')->after('description')->nullable()->index();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_refund_lines', function(Blueprint $table){
			$table->dropColumn('trade_id');
			$table->dropColumn('work_type_id');
		});
	}

}
