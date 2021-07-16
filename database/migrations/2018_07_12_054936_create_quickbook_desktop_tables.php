<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Services\QuickBookDesktop\QBDesktopUtilities;

class CreateQuickbookDesktopTables extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{	

		QBDesktopUtilities::initialize(QBDesktopUtilities::dsn());

		Schema::table('quickbooks_user', function($table){
			$table->integer('company_id')->unsigned();
			$table->string('password_key')->nullable();
		});

	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('quickbooks_user');
		Schema::drop('quickbooks_ticket');		
		Schema::drop('quickbooks_recur');
		Schema::drop('quickbooks_queue');
		Schema::drop('quickbooks_log');
		Schema::drop('quickbooks_config');
		Schema::drop('quickbooks_oauth');
	}

}
