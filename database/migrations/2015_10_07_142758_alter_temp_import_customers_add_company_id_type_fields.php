<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTempImportCustomersAddCompanyIdTypeFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('temp_import_customers', function($table){
			$table->dropColumn('session_id');
			$table->integer('company_id');
			$table->boolean('is_valid')->default(false);
			$table->text('errors')->nullable();
		}); 
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('temp_import_customers', function($table){
			$table->string('session_id');
			$table->dropColumn('company_id');
			$table->dropColumn('is_valid');
			$table->dropColumn('errors');
		}); 
	}

}
