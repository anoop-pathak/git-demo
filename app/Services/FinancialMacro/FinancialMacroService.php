<?php
namespace App\Services\FinancialMacro;

use Request;
use App\Models\FinancialCategory;
use Illuminate\Support\Facades\DB;
use App\Repositories\FinancialMacrosRepository;

class FinancialMacroService
{

    public function __construct(FinancialMacrosRepository $repo)
    {
        $this->repo = $repo;
    }

    public function save($name, $type, $meta = [])
    {
        $tradeId = (int)issetRetrun($meta, 'trade_id');
        $forAllTrades = (int)issetRetrun($meta, 'for_all_trades');

        $details = isSetNotEmpty($meta, 'details');

        $this->repo->save($name, $type, $tradeId, $forAllTrades, $details, $meta);

        return true;
    }

    /**
     * Get macro
     * @param  int $macroId MacroId
     * @return macro
     */
    public function getMacro($macroId)
    {
        $macro = $this->repo->getById($macroId);

        $companyId = getScopeId();

        $forSubId  = null;
        if(Request::has('for_sub_id')) {
            $forSubId = Request::get('for_sub_id');
        }

        $categories = FinancialCategory::where('company_id', $companyId)
        ->with([
            'macroDetails' => function ($query) use ($macroId, $forSubId) {
                $query->join(DB::raw("(SELECT * FROM macro_details WHERE macro_id = '{$macroId}')AS macro_details"), 'macro_details.product_id', '=', 'financial_products.id')
                    ->where('macro_details.macro_id', $macroId)
                    ->select('financial_products.*', 'macro_details.quantity', 'macro_details.order', 'financial_products.name as product_name')
                    ->orderBy('macro_details.id', 'asc');

                // remove other subcontractor products from list
                if($forSubId) {
                    $query->addSelect(DB::raw("IF(financial_products.labor_id = $forSubId, financial_products.unit_cost, null) as unit_cost,IF(financial_products.labor_id = $forSubId, financial_products.selling_price, null) as selling_price"));
                    $query->where(function($query) use($forSubId){
                        $query->where('financial_products.labor_id', $forSubId)
                            ->orWhereNull('financial_products.labor_id');
                    });
                }
            },
            'macroDetails.supplier'
        ])->orderBy('order', 'asc') 
        ->get();

        unset($macro->categories);
        unset($macro->details);

        $macro->categoroes = $categories;

        return $macro;
    }
}
