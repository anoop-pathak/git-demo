<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAfDocumentsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('af_documents', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->index();
			$table->integer('group_id')->index()->comment("standard user group id => 3");
			$table->integer('document_id')->nullable()->comment('JobProgress document id.');
			$table->string('af_id')->nullable()->comment('id');
			$table->string('folder_id')->nullable()->comment('FolderId');
			$table->string('name')->nullable()->comment('Name');
			$table->string('content_type')->nullable()->comment('ContentType');
			$table->string('type')->nullable()->comment('Type');
			$table->boolean('is_public')->default(false)->comment('IsPublic');
			$table->string('body_length')->nullable()->comment('BodyLength');
			$table->string('body_length_compressed')->nullable()->comment('BodyLengthCompressed');
			$table->text('description')->nullable()->comment('Description');
			$table->string('keywords')->nullable()->comment('Keywords');
			$table->boolean('is_internal_use_only')->default(false)->comment('IsInternalUseOnly');
			$table->string('author_id')->nullable()->comment('AuthorId');
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
		Schema::drop('af_documents');
	}

}
