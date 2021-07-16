<?php
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Eloquent::unguard();

        $this->call('PermissionsTableSeeder');
        // $this->call('UserTableSeeder');
        // $this->call('SuppliersTableSeeder');
        $this->call('GroupTableSeeder');
        $this->call('DepartmentTableSeeder');
        $this->call('TradeTableSeeder');
        $this->call('WorkType1TableSeeder');
        $this->call('StatesTableSeeder');
        $this->call('CountryTableSeeder');
        // $this->call('AccountManagersTableSeeder');
        // $this->call('DoubleDcSubscriberSeeder');
        // $this->call('SubscribersTableSeeder');
        $this->call('LibraryStepTableSeeder');
        $this->call('ProductTableSeeder');
        $this->call('SubscriptionPlanTableSeeder');
        $this->call('DiscountCouponsTableSeeder');
        $this->call('TemplatesTableSeeder');
        $this->call('SetupActionTableSeeder');
        $this->call('TimezonesTableSeeder');
        $this->call('EVFileTypeTableSeeder');
        $this->call('EVMeasurmentsTableSeeder');
        $this->call('EVStatusTableSeeder');
        $this->call('EVSubStatusTableSeeder');
        $this->call('FlagsTableSeeder');
        $this->call('OauthClientsTableSeeder');
        $this->call('ManufacturersTableSeeder');
		$this->call('EstimateTypesTableSeeder');
        $this->call('PaymentMethodsTableSeeder');
		$this->call('FinancialAccountTypesTableSeeder');
		$this->call('VendorTypesTableSeeder');
		$this->call('SubscriberStageAttributesTableSeeder');
    }
}
