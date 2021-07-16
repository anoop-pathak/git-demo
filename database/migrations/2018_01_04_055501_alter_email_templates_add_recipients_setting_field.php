<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterEmailTemplatesAddRecipientsSettingField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('email_templates', function(Blueprint $table)
		{
			$table->string('recipients_setting');
		});

		$setting = json_encode([
			'to'  => [],
			'cc'  => [],
			'bcc' => []
		]);

		DB::statement("UPDATE email_templates set recipients_setting = '{$setting}'");
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('email_templates', function(Blueprint $table)
		{
			$table->dropColumn('recipients_setting');
		});
	}

}
