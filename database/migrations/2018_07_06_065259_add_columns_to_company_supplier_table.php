<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnsToCompanySupplierTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('company_supplier', function(Blueprint $table) {
			$table->string('branch_id')->nullable();
			$table->string('branch_address')->nullable();
			$table->string('manager_name')->nullable();
			$table->string('email')->nullable();
			$table->string('phone')->nullable();
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
		Schema::table('company_supplier', function(Blueprint $table) {
			$table->dropColumn('branch_id');
			$table->dropColumn('branch_address');
			$table->dropColumn('manager_name');
			$table->dropColumn('email');
			$table->dropColumn('phone');
			$table->dropColumn('deleted_at');
			$table->dropColumn('created_at');
			$table->dropColumn('updated_at');
		});
	}

}
