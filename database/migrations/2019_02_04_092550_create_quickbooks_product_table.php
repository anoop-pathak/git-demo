<?php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQuickbooksProductTable extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('quickbooks_product', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->index();
			$table->string('name');
			$table->string('parent_name')->nullable();
			$table->string('parent_list_id')->nullable();
			$table->string('sale_tax_code_name')->nullable();
			$table->string('sale_tax_code_list_id')->nullable();
			$table->string('price')->nullable();
			$table->string('account_list_id')->nullable();
			$table->string('account_name')->nullable();
			$table->string('sale_description')->nullable();
			$table->string('sale_price')->nullable();
			$table->string('sale_income_account_name')->nullable();
			$table->string('sale_income_account_list_id')->nullable();
			$table->string('purchase_description')->nullable();
			$table->string('purchase_cost')->nullable();
			$table->string('purchase_expenses_account_name')->nullable();
			$table->string('purchase_expenses_account_list_id')->nullable();
			$table->integer('sub_lavel')->default(0);
			$table->string('uom_name')->nullable();
			$table->string('uom_list_id')->nullable();
			$table->timestamp('product_created_at');
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
		Schema::drop('quickbooks_product');
	}
}