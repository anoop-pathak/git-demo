<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterGoogleClientAddCompanyIdField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('google_clients', function($table){
			$table->integer('company_id')->nullable()->after('user_id');
		});

		DB::statement('ALTER TABLE google_clients MODIFY COLUMN user_id INTEGER');
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('google_clients', function($table){
			$table->dropColumn('company_id');
		});

		DB::statement('ALTER TABLE google_clients MODIFY COLUMN user_id INTEGER NOT NULL');
	}

}
