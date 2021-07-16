<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQbdInvoicesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('qbd_invoices', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id');
			$table->string('qb_desktop_txn_id');
			$table->string('customer_ref');
			$table->dateTime('txn_date');
			$table->string('txn_number');
			$table->string('edit_sequence');
			$table->string('ref_number');
			$table->dateTime('due_date');
			$table->dateTime('qb_creation_date');
			$table->dateTime('qb_modified_date');
			$table->string('item_sales_tax_ref');
			$table->integer('sales_tax_percentage');
			$table->float('sales_tax_total',10,2);
			$table->float('applied_amount',10,2);
			$table->float('sub_total',10,2);
			$table->float('balance_remaining',10,2);
			$table->string('memo');
			$table->text('meta');
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
		Schema::drop('qbd_invoices');
	}

}
