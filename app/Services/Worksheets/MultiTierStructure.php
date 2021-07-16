<?php

namespace App\Services\Worksheets;

class MultiTierStructure
{
    public $worksheet = null;
    public $margin = null;
    public $line_tax = null;
    public $line_margin_markup = null;

    public function makeMultiTierStructure($collection = null, $addSum = false, $sellingPrice = false, &$goRecusive = [], $level = 0, $useLivePricing = null)
    {
        $tree = $goRecusive;

        if ($collection) {
            foreach ($collection as $key => $value) {
                if($useLivePricing) {
					$value->live_pricing = $value->getLivePricing();
					unset($value->livePricingThroughBranchCode);
					unset($value->livePricingThroughProductId);
                }

                if (ine($value, 'tier1') && ine($value, 'tier2') && ine($value, 'tier3')) {
                    $tree[$value['tier1']][$value['tier2']][$value['tier3']][] = (object)$value;
                    $tree = $this->setTierDetails($value, $tree);
                } elseif (ine($value, 'tier1') && ine($value, 'tier2')) {
                    $tree[$value['tier1']][$value['tier2']][] = (object)$value;
                    $tree = $this->setTierDetails($value, $tree);
                } elseif (ine($value, 'tier1')) {
                    $tree[$value['tier1']][] = (object)$value;
                    $tree = $this->setTierDetails($value, $tree);
                } else {
                    $tree[] = (object)$value;
                }
            }
        }

        $data = [];
        $level += 1;
        foreach ($tree as $key => $value) {
            if (is_array($value)) {
                $collection = (object)[
                    'type' => 'collection',
                    'tier' => $level,
                    'tier_name' => (string)$key,
                    'tier_description' => issetRetrun($value, 'tier_description') ?: '',
                    'tier_measurement_id'	=> issetRetrun($value, 'tier_measurement_id') ?: null,
					'tier_collapse' => (int)ine($value, 'collapse'),
                    'data' => $this->makeMultiTierStructure(null, $addSum, $sellingPrice, $value, $level),
                ];

                if ($addSum) {
                    $sum = $this->addMultiTierSum($collection, $sellingPrice);
                    $collection->sum = $sum;
                }

                $data[] = $collection;
            }

            if (is_object($value)) {
                $data[] = (object)[
                    'type' => 'item',
                    'data' => $value,
                ];
            }
        }

        return $data;
    }

    private function addMultiTierSum(&$collection, $sellingPrice, $sum = 0)
    {
        $lineTax = 0;
        $lineMargin = 0;
        foreach ($collection->data as $key => $value) {
            if ($value->type == 'collection') {
                $sum = $this->addMultiTierSum($value, $sellingPrice, $sum);
            } else {
                $item = $value->data;

                if ($sellingPrice) {
                    $sum += numberFormat($item->selling_price * $item->quantity);
                    $cost = $item->selling_price * $item->quantity;
                } else {
                    $sum += numberFormat($item->unit_cost * $item->quantity);
                    $cost = $item->unit_cost * $item->quantity;
                }

                if ($this->line_tax && !empty($item->line_tax)) {
                    $lineTax += calculateTax($cost, $item->line_tax);
                }

                if ($this->line_margin_markup && !empty($item->line_profit)) {
                    $lineMargin += getWorksheetMarginMarkup($this->margin, $cost, $item->line_profit);
                }
            }
        }

        return $sum + $lineTax + $lineMargin;
    }

    private function setTierDetails($value, $tree)
	{
		$setting = isset($value['setting']) ? $value['setting'] : [];
        $tier1Collapse = $tier2Collapse = $tier3Collapse = false;

		if(ine($tree[$value['tier1']], 'collapse') || ine($setting, 'tier1_collapse')) {
			$tier1Collapse = true;
		}

        if($tier1Collapse
			|| ine($setting, 'tier2_collapse')
			|| (isset($value['tier1']) && isset($value['tier2']) && isset($tree[$value['tier1']][$value['tier2']]) && ine($tree[$value['tier1']][$value['tier2']], 'collapse'))) {
			$tier2Collapse = true;
		}

        $tier3Collapse = $tier2Collapse ?: ine($setting, 'tier3_collapse');

        if(ine($value, 'tier1')) {

            if(!ine($tree[$value['tier1']], 'collapse')) {
				$tree[$value['tier1']]['collapse'] = $tier1Collapse;
			}

            if(!ine($tree[$value['tier1']], 'tier_description') && ine($value, 'tier1_description')) {
				$tree[$value['tier1']]['tier_description'] = $value['tier1_description'];
            }

            if(!ine($tree[$value['tier1']], 'tier_measurement_id') && ine($value, 'tier1_measurement_id')) {
				$tree[$value['tier1']]['tier_measurement_id'] = $value['tier1_measurement_id'];
			}
		}

        if(ine($value, 'tier2')) {

            if(!ine($tree[$value['tier1']][$value['tier2']], 'collapse')) {
				$tree[$value['tier1']][$value['tier2']]['collapse'] = $tier2Collapse;
			}

            if(!ine($tree[$value['tier1']][$value['tier2']], 'tier_description')
				&& ine($value, 'tier2_description')) {
				$tree[$value['tier1']][$value['tier2']]['tier_description'] = $value['tier2_description'];
            }

            if(!ine($tree[$value['tier1']][$value['tier2']], 'tier_measurement_id')
				&& ine($value, 'tier2_measurement_id')) {
				$tree[$value['tier1']][$value['tier2']]['tier_measurement_id'] = $value['tier2_measurement_id'];
			}
		}

        if(ine($value, 'tier3')) {

            if(!ine($tree[$value['tier1']][$value['tier2']][$value['tier3']], 'collapse')) {
				$tree[$value['tier1']][$value['tier2']][$value['tier3']]['collapse'] = $tier3Collapse;
			}

            if(!ine($tree[$value['tier1']][$value['tier2']][$value['tier3']], 'tier_description')
				&& ine($value, 'tier3_description')) {
				$tree[$value['tier1']][$value['tier2']][$value['tier3']]['tier_description'] = $value['tier3_description'];
            }

            if(!ine($tree[$value['tier1']][$value['tier2']][$value['tier3']], 'tier_measurement_id')
				&& ine($value, 'tier3_measurement_id')) {
				$tree[$value['tier1']][$value['tier2']][$value['tier3']]['tier_measurement_id'] = $value['tier3_measurement_id'];
			}
		}

        return $tree;
	}
}
