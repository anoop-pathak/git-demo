<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterEmailsAddStatusField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('emails', function($table) {
			$table->string('status')->nullable();
		});

		DB::table('emails')->where('type', '!=', 'received')->update(['status' => 'sent']);
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('emails', function($table) {
			$table->dropColumn('status');
		});
	}

}
