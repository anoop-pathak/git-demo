<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAfAttachmentsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('af_attachments', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->index();
			$table->integer('group_id')->index()->comment("standard user group id => 3");
			$table->integer('attachment_id')->nullable()->comment('JobProgress attachment id.');
			$table->string('af_id')->nullable()->comment('id');
			$table->string('af_owner_id')->nullable()->comment('OwnerId');
			$table->string('feed_item_id')->nullable()->comment('FeedItemId');
			$table->string('parent_id')->nullable()->comment('ParentId');
			$table->string('account_id')->nullable()->comment('AccountId');
			$table->string('name')->nullable()->comment('Name');
			$table->string('content_type')->nullable()->comment('ContentType');
			$table->string('body_length')->nullable()->comment('BodyLength');
			$table->string('body_length_compressed')->nullable()->comment('BodyLengthCompressed');
			$table->text('description')->nullable()->comment('Description');
			$table->boolean('is_private')->default(false)->comment('IsPrivate');
			$table->text('options')->nullable()->comment('full document object.');
			$table->string('created_by')->index()->nullable()->comment('CreatedById');
			$table->string('updated_by')->index()->nullable()->comment('LastModifiedById');
			$table->string('csv_filename')->nullable()->comment('name of file from which data is imported.');
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('af_attachments');
	}

}
