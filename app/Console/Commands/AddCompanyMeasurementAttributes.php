<?php

namespace App\Console\Commands;

use App\Models\CompanyTrade;
use App\Models\MeasurementAttribute;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AddCompanyMeasurementAttributes extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:add_company_measurement_attributes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add company measurement attributes.';

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
        $roofingAttributes = [
            'Facets',
            'Pitch',
            'Ridges',
            'Hips',
            'Valleys',
            'Rakes',
            'Eaves',
            'Flashing',
            'Step Flashing',
            'Waste Factor'
        ];

        $roofingSquaresAttribute = 'Squares';

        $roofingIds = [8, 9];
        $companies = CompanyTrade::whereIn('trade_id', $roofingIds)
            ->get()
            ->groupBy('company_id');

        $now = \Carbon\Carbon::now();

        //create attributes for roofing & siding
        foreach ($companies as $companyId => $companyTrades) {
            $measurementAttributes = [];
            foreach ($companyTrades as $companyTrade) {
                foreach ($roofingAttributes as $attribute) {
                    $slug = str_replace(' ', '_', strtolower($attribute));
                    $msAttribute = MeasurementAttribute::firstOrNew([
                        'slug'     => $slug,
                        'trade_id' => $companyTrade->trade_id,
                        'company_id' => $companyId,
                    ]);
                    $msAttribute->name = $attribute;
                    $msAttribute->save();
                }
                if($companyTrade->trade_id == 8) {
                    $slug = str_replace(' ', '_', strtolower($roofingSquaresAttribute));
                    $msAttribute = MeasurementAttribute::firstOrNew([
                        'slug'     => $slug,
                        'trade_id' => $companyTrade->trade_id,
                        'company_id' => $companyId,
                    ]);
                    $msAttribute->name = $roofingSquaresAttribute;
                    $msAttribute->save();   
                }
            }
        }


        //create atributes for all trades except roofing & siding
        $allTradeAttribute = ['Linear ft', 'Square ft'];
        $companyTrades = CompanyTrade::whereNotIn('trade_id', $roofingIds)
            ->get()
            ->groupBy('company_id');
        foreach ($companyTrades as $companyId => $companyTrades) {
            $measurementAttributes = [];    
            foreach ($companyTrades as $companyTrade) {
                foreach ($allTradeAttribute as $attribute) {
                    $slug = str_replace(' ', '_', strtolower($attribute));
                    $msAttribute = MeasurementAttribute::firstOrNew([
                        'slug'     => $slug,
                        'trade_id' => $companyTrade->trade_id,
                        'company_id' => $companyId,
                    ]);
                    $msAttribute->name = $attribute;
                    $msAttribute->save();
                }
            }
        }
    }
}
