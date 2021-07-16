<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddExtensionFieldToAfAttachmentsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('af_attachments', function(Blueprint $table)
		{
			$table->string('extension')->nullable()->after('content_type');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('af_attachments', function(Blueprint $table)
		{
			$table->dropColumn('extension');
		});
	}

}
