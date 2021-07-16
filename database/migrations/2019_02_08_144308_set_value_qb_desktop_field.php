<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SetValueQbDesktopField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement("UPDATE customers SET qb_desktop_id=null,qb_desktop_delete=false,qb_desktop_sequence_number=null where qb_desktop_delete = true");
		DB::statement("UPDATE jobs SET qb_desktop_id=null,qb_desktop_delete=false,qb_desktop_sequence_number=null where qb_desktop_delete = true");
		DB::statement("UPDATE job_credits SET qb_desktop_txn_id =null, qb_desktop_id=null,qb_desktop_delete=false,qb_desktop_sequence_number=null where qb_desktop_delete = true");
		DB::statement("UPDATE job_invoices SET qb_desktop_txn_id =null, qb_desktop_id=null,qb_desktop_delete=false,qb_desktop_sequence_number=null where qb_desktop_delete = true");
		DB::statement("UPDATE job_payments SET qb_desktop_txn_id =null, qb_desktop_id=null,qb_desktop_delete=false,qb_desktop_sequence_number=null where qb_desktop_delete = true");

	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		//
	}

}
