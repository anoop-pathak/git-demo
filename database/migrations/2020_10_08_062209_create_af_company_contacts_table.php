<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAfCompanyContactsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('af_company_contacts', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->index();
			$table->integer('group_id')->index()->comment("standard user group id => 3");
			$table->string('company_contact_id')->nullable()->comment('company contact id.');
			$table->string('af_id')->nullable()->comment('id');
			$table->string('af_owner_id')->nullable()->comment('OwnerId');
			$table->string('account_id')->nullable()->comment('AccountId');
			$table->string('salutation')->nullable()->comment('Salutation');
			$table->string('first_name')->nullable()->comment('FirstName');
			$table->string('last_name')->nullable()->comment('LastName');
			$table->string('email')->nullable()->comment('Email');
			$table->string('title')->nullable()->comment('Title');
			$table->string('phone')->nullable()->comment('Phone');
			$table->string('fax')->nullable()->comment('Fax');
			$table->string('mobile_phone')->nullable()->comment('MobilePhone');
			$table->text('other_address')->nullable()->comment('OtherStreet + Other City + Other State + Other Postal Code + Other Country');
			$table->string('other_street')->nullable()->comment('OtherStreet');
			$table->string('other_city')->nullable()->comment('OtherCity');
			$table->string('other_state')->nullable()->comment('OtherState');
			$table->string('other_postal_code')->nullable()->comment('OtherPostalCode');
			$table->string('other_country')->nullable()->comment('OtherCountry');
			$table->string('other_latitude')->nullable()->comment('OtherLatitude');
			$table->string('other_longitude')->nullable()->comment('OtherLongitude');
			$table->text('mailing_address')->nullable()->comment('MailingStreet + Mailing City + Mailing State + Mailing Postal Code + Mailing Country');
			$table->string('mailing_street')->nullable()->comment('MailingStreet');
			$table->string('mailing_city')->nullable()->comment('MailingCity');
			$table->string('mailing_state')->nullable()->comment('MailingState');
			$table->string('mailing_postal_code')->nullable()->comment('MailingPostalCode');
			$table->string('mailing_country')->nullable()->comment('MailingCountry');
			$table->string('mailing_latitude')->nullable()->comment('MailingLatitude');
			$table->string('mailing_longitude')->nullable()->comment('MailingLongitude');
			$table->text('description')->nullable()->comment('Description');
			$table->text('options')->nullable()->comment('store all data in json format.');
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
		Schema::drop('af_company_contacts');
	}

}
