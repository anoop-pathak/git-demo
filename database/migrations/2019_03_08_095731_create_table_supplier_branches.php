<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTableSupplierBranches extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('supplier_branches', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id');
			$table->integer('company_supplier_id');
			$table->string('branch_id')->nullable();
			$table->string('branch_code')->nullable();
			$table->string('name')->nullable();
			$table->string('address')->nullable();
			$table->string('city')->nullable();
			$table->string('state')->nullable();
			$table->string('zip')->nullable();
			$table->string('email')->nullable();
			$table->string('phone')->nullable();
			$table->string('manager_name')->nullable();
			$table->string('logo')->nullable();
			$table->mediumText('meta')->nullable();
			$table->timestamps();
			$table->softDeletes();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('supplier_branches');
	}

}
