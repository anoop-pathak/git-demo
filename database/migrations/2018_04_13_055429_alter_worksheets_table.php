<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterWorksheetsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('worksheets', function(Blueprint $table)
		{
			$table->string('material_tax_rate')->nullable()->after('tax_rate');
			$table->string('labor_tax_rate')->nullable()->after('material_tax_rate');
			$table->boolean('multi_tier')->default(false)->after('type');
			$table->boolean('hide_pricing')->default(false);
			$table->integer('material_custom_tax_id')->nullable()->after('material_tax_rate');
			$table->integer('labor_custom_tax_id')->nullable()->after('labor_tax_rate');
			$table->boolean('re_calculate')->after('labor_custom_tax_id')->default(false);
		});
		DB::statement("UPDATE worksheets set hide_pricing = material_pricing, re_calculate = true");
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('worksheets', function(Blueprint $table)
		{
			$table->dropColumn('labor_tax_rate');
			$table->dropColumn('material_tax_rate');
			$table->dropColumn('multi_tier');
			$table->dropColumn('hide_pricing');
			$table->dropColumn('material_custom_tax_id');
			$table->dropColumn('labor_custom_tax_id');
			$table->dropColumn('re_calculate');
		});
	}

}
