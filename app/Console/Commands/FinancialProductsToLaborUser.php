<?php

namespace App\Console\Commands;

use App\Models\FinancialProduct;
use App\Models\Labour;
use App\Models\Resource;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class FinancialProductsToLaborUser extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:finance_to_labor_user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        start: {
        $products = FinancialProduct::whereIn('category_id', function ($query) {
            $query->select('id')->from('financial_categories')->whereName('LABOR');
        })->whereNull('labor_id');

        $productQuery = clone $products;

        $products->chunk(100, function ($products) {
            foreach ($products as $product) {
                $this->shiftToUser($product);
            }
        });
        }

        if ($productQuery->count()) {
            goto start;
        }

        $this->info('Labour created successfully.');
    }

    private function shiftToUser($product)
    {
        DB::beginTransaction();
        try {
            $user = new User(['first_name' => $product->name]);
            $user->company_id = $product->company_id;
            $user->group_id = User::GROUP_LABOR;
            $user->save();
            $user->resource_id = $this->createResourceDir($user);
            $user->save();
            UserProfile::firstOrCreate(['user_id' => $user->id]);

            $product->labor_id = $user->id;
            $product->save();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
        DB::commit();
    }


    /**
     * @create resource directory fot labour
     * @return labour resource directory id
     */
    private function createResourceDir($labour)
    {
        $resourceService = App::make(ResourceServices::class);

        $companyId = $labour->company_id;
        $parentDir = Resource::name(Resource::LABOURS)->company($companyId)->first();
        if (!$parentDir) {
            $root = Resource::companyRoot($companyId);
            $parentDir = $resourceService->createDir(Resource::LABOURS, $root->id);
        }
        $dirName = $labour->first_name . '_' . $labour->last_name . '_' . $labour->id;
        $labourDir = $resourceService->createDir($dirName, $parentDir->id);

        return $labourDir->id;
    }
}
