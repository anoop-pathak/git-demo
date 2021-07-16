<?php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
class AlterTableWorksheetsAddUpdateTaxOrder extends Migration {
 	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
 	public function up()
 	{
 		Schema::table('worksheets', function(Blueprint $table) {
 			$table->boolean('update_tax_order')->default(false);
 		});
 	}
 	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
 	public function down()
 	{
 		Schema::table('worksheets', function(Blueprint $table) {
 			$table->dropColumn('update_tax_order');
 		});
 	}
 }