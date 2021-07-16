<?php
namespace App\Services\QuickBookDesktop\Entity;

use App\Services\QuickBookDesktop\Setting\Settings;
use Carbon\Carbon;
use Log;
use Auth;
use Exception;
use App\Models\ItemSalesTaxGroup;
use App\Models\TaxGroupItemTax;
use App\Models\ItemSalesTax;
use App\Models\CustomTax;
use App\Models\SalesTaxCode;

class Tax
{
	public function __construct(
		Settings $settings
	) {
		$this->settings = $settings;
    }

    public function getTaxCodeByQbdId($id)
    {
        return SalesTaxCode::withTrashed()
            ->where('company_id', getScopeId())
            ->where('qb_desktop_id', $id)
            ->first();
    }

    public function getTaxByQbdId($id)
    {
        return CustomTax::withTrashed()
            ->where('company_id', getScopeId())
            ->where('qb_desktop_id', $id)
            ->first();
    }

    public function getCustomTax($id)
    {
        return CustomTax::withTrashed()
            ->where('company_id', getScopeId())
            ->where('id', $id)->first();
    }

    public function getTaxableCode()
    {
        return SalesTaxCode::withTrashed()
            ->where('company_id', getScopeId())
            ->where('taxable', true)
            ->first();
    }

    public function getNonTaxableCode()
    {
        return SalesTaxCode::withTrashed()
            ->where('company_id', getScopeId())
            ->where('taxable', false)
            ->first();
    }

    function saveItemSalesTax($taxes, $task)
	{
		try {
            foreach ($taxes as $tax) {

                $itemTax = ItemSalesTax::firstOrNew([
                    'qb_desktop_id' => $tax['ListID'],
                    'qb_username' => $task->qb_username,
                    'company_id' => getScopeId()
                ]);

                $itemTax->name = $tax['Name'];
                $itemTax->description = $tax['ItemDesc'];
                $itemTax->tax_rate = $tax['TaxRate'];
                $itemTax->active = ($tax['IsActive'] == 'true') ? 1 : 0;
                $itemTax->qb_vendor_id = $tax['TaxVendorRef']['ListID'];
                $itemTax->qb_desktop_sequence_number = $tax['EditSequence'];
                $itemTax->save();
            }

            $this->syncQBDTaxes($task->qb_username);
		} catch (Exception $e) {

			Log::error($e);

			throw $e;
		}
    }

    function saveItemSalesTaxGroups($groups, $task)
    {
        foreach ($groups as $group) {

            $companyId = getScopeId();

            $itemSalesTaxeIds = [];

            $itemSalesTaxRef = [];

            foreach ($group['ItemSalesTaxRef'] as $tax) {
                $itemSalesTaxeIds[] = $tax['ListID'];
            }

            $itemSalesTaxes = ItemSalesTax::where('qb_username', $task->qb_username)
                ->whereIn('qb_desktop_id', $itemSalesTaxeIds)
                ->get();

            $item = ItemSalesTaxGroup::firstOrNew([
                'qb_desktop_id' => $group['ListID'],
                'qb_username' => $task->qb_username,
                'company_id' => $companyId
            ]);

            // If all tax rates do not exists
            if (count($itemSalesTaxeIds) != count($itemSalesTaxes)) {
                Log::warning("Tax group can't be saved");
                continue;
            }

            $item->name = $group['Name'];
            $item->description = $group['ItemDesc'];
            $item->active = ($group['IsActive'] == 'true') ? 1 : 0;
            $item->qb_desktop_sequence_number = $group['EditSequence'];
            $item->save();

            // delete old rates attached
            $item->rates()->delete();

            foreach ($itemSalesTaxes as $saleTax) {

                $itemSalesTaxRef[] = new TaxGroupItemTax([
                    'company_id' => $companyId,
                    'tax_id' => $saleTax->id,
                    'group_id' => $item->id
                ]);
            }

            $item->rates()->saveMany($itemSalesTaxRef);
        }

        $this->syncQBDTaxes($task->qb_username);
    }

    /**
     * Sync QBD Taxes
     */

    public function syncQBDTaxes($userName)
    {
        $itemSalesTaxGroup = ItemSalesTaxGroup::with(['rates.itemSalesTax'])
            ->where('qb_username', $userName)
            ->get();

        $taxCodesProcessed = [];

        foreach ($itemSalesTaxGroup as $taxGroup) {

            $taxRate = null;

            if($taxGroup->rates) {
                foreach ($taxGroup->rates as $rate) {
                    $taxRate = $taxRate + $rate->itemSalesTax->tax_rate;
                }
            }

            if($taxRate) {

                if ($taxGroup->active) {
                    $taxCodesProcessed[] = $taxGroup->qb_desktop_id;
                }

                $this->updateOrCreateTax([
                    'name' => $taxGroup->name,
                    'qb_desktop_id' => $taxGroup->qb_desktop_id,
                    'qb_desktop_sequence_number' => $taxGroup->qb_desktop_sequence_number,
                    'tax_rate' => $taxRate
                ]);
            }
        }

        $itemSalesTaxes = ItemSalesTax::where('qb_username', $userName)->get();

        foreach ($itemSalesTaxes as $saleTax) {

            if ($saleTax->active) {
                $taxCodesProcessed[] = $saleTax->qb_desktop_id;
            }

            $this->updateOrCreateTax([
                'name' => $saleTax->name,
                'qb_desktop_id' => $saleTax->qb_desktop_id,
                'qb_desktop_sequence_number' => $saleTax->qb_desktop_sequence_number,
                'tax_rate' => $saleTax->tax_rate
            ]);
        }

        // Soft Delete all other tax codes
        CustomTax::where('company_id', getScopeId())
            ->whereNotNull('qb_desktop_id')
            ->whereNotIn('qb_desktop_id', $taxCodesProcessed)
            ->delete();

        // make sure all that came as active stays active in our database as well
        CustomTax::where('company_id', getScopeId())
            ->whereNotNull('qb_desktop_id')
            ->whereIn('qb_desktop_id', $taxCodesProcessed)
            ->restore();
    }

    private function updateOrCreateTax($meta)
    {
        $customTax = CustomTax::where('company_id', getScopeId())
            ->withTrashed()
            ->where('qb_desktop_id',  $meta['qb_desktop_id'])
            ->first();

        if ($customTax) {

            $customTax->title = $meta['name'];
            $customTax->tax_rate = $meta['tax_rate'];
            $customTax->qb_desktop_sequence_number = $meta['qb_desktop_sequence_number'];
            $customTax->updated_at = Carbon::now();
            $customTax->save();

        } else if (!$customTax) {

            $customTax = new CustomTax([
                'company_id' => getScopeId(),
                'qb_desktop_id' => $meta['qb_desktop_id']
            ]);

            $customTax->title = $meta['name'];
            $customTax->tax_rate = $meta['tax_rate'];
            $customTax->qb_desktop_sequence_number = $meta['qb_desktop_sequence_number'];
            $customTax->created_by = Auth::user()->id;
            $customTax->save();
        }
    }

    public function storeTaxCodes($taxCodes, $username)
    {
        foreach ($taxCodes as $entity) {

            $code = SalesTaxCode::firstOrNew([
                'qb_desktop_id' => $entity['ListID'],
                'qb_username' => $username,
                'company_id' => getScopeId()
            ]);

            $code->name = $entity['Name'];
            $code->description = $entity['Desc'];
            $code->taxable = ($entity['IsTaxable'] == 'true') ? 1: 0;
            $code->active = ($entity['IsActive'] == 'true') ? 1 : 0;
            $code->qb_desktop_sequence_number = $entity['EditSequence'];
            $code->save();
        }
    }
}