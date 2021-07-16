<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTemplatesChangeContentFiledDataType extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('templates',function($table){
			$table->dropColumn('content');
		});

		Schema::table('templates',function($table){
			$table->mediumText('content');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('templates',function($table){
			$table->dropColumn('content');
		});

		Schema::table('templates',function($table){
			$table->text('content');
		});
	}

}
