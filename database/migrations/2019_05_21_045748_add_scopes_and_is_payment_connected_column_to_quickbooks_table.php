<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddScopesAndIsPaymentConnectedColumnToQuickbooksTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('quickbooks', function(Blueprint $table)
		{
			$table->string('scopes')->after('access_token_secret')->nullable();
			$table->boolean('is_payments_connected')->default(0)->before('created_at');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('quickbooks', function(Blueprint $table)
		{
			$table->dropColumn('scopes');
			$table->dropColumn('is_payments_connected');
		});
	}

}
