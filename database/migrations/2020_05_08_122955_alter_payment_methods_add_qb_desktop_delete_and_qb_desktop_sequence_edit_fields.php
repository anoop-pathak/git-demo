<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterPaymentMethodsAddQbDesktopDeleteAndQbDesktopSequenceEditFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('payment_methods', function(Blueprint $table)
		{
			$table->string('type')->after('quickbook_sync_token')->nullable();
			$table->boolean('qb_desktop_delete')->after('quickbook_sync_token')->default(false);
			$table->string('qb_desktop_sequence_number')->after('quickbook_sync_token')->nullable();
			$table->string('qb_desktop_id')->after('quickbook_sync_token')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('payment_methods', function(Blueprint $table)
		{
			$table->dropColumn('qb_desktop_id');
			$table->dropColumn('qb_desktop_delete');
			$table->dropColumn('qb_desktop_sequence_number');
			$table->dropColumn('type');
		});
	}

}
