<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterDivisionsAddAddressIdAndOtherColumns extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('divisions', function(Blueprint $table) {
			$table->integer('address_id')->nullable();
			$table->string('email')->nullable();
			$table->string('phone')->nullable();
			$table->string('phone_ext')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('divisions', function(Blueprint $table) {
			$table->dropColumn('address_id');
			$table->dropColumn('email');
			$table->dropColumn('phone');
			$table->dropColumn('phone_ext');
		});
	}

}
