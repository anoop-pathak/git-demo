<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterQuickbookWebhookEntriesAddCompanyIdAndMetaColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('quickbook_webhook_entries', function(Blueprint $table)
		{
			$table->integer('company_id')->nullable()->after('realm_id');
			
			DB::statement('ALTER TABLE `quickbook_webhook_entries` MODIFY `quickbook_webhook_id` INTEGER UNSIGNED DEFAULT NULL;');

			DB::statement('ALTER TABLE `quickbook_webhook_entries` MODIFY `status` VARCHAR(200) DEFAULT NULL;');

			$table->text('extra')->nullable()->after('realm_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('quickbook_webhook_entries', function(Blueprint $table)
		{
			DB::statement('ALTER TABLE `quickbook_webhook_entries` MODIFY `quickbook_webhook_id` INTEGER UNSIGNED NOT NULL;');

			DB::statement('ALTER TABLE `quickbook_webhook_entries` MODIFY `status` INTEGER UNSIGNED NOT NULL;');
			
			$table->dropColumn('company_id');

			$table->dropColumn('extra');
		});
	}

}
