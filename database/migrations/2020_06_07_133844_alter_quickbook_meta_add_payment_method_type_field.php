<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterQuickbookMetaAddPaymentMethodTypeField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('quickbook_meta', function(Blueprint $table)
		{
			$table->string('payment_method_type')->after('type')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('quickbook_meta', function(Blueprint $table)
		{
			$table->dropColumn('payment_method_type');
		});
	}

}
