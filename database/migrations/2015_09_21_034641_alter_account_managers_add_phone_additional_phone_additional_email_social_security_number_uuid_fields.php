<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterAccountManagersAddPhoneAdditionalPhoneAdditionalEmailSocialSecurityNumberUuidFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('account_managers', function($table){
			$table->text('additional_emails')->nullable();
			$table->text('additional_phones')->nullable();
			$table->string('social_security_number')->nullable();
			$table->text('uuid')->nullable();
			$table->boolean('for_all_trades')->default(0);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('account_managers', function($table){
			$table->dropColumn('additional_emails');
			$table->dropColumn('additional_phones');
			$table->dropColumn('social_security_number');
			$table->dropColumn('uuid');
			$table->dropColumn('for_all_trades');
		});
	}

}
