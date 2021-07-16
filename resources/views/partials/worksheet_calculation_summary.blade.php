<?php
	$noChargeAmt = 0;

	if(isset($worksheet->meta['no_charge_amount'])) {
		$noChargeAmt = $worksheet->meta['no_charge_amount'];
	}

	$subTotal = $worksheet->total;
	if($worksheet->useSellingPrice()) {
		$subTotal = $worksheet->selling_price_total;
	}

	$showSummary = true;
	if($worksheet->hide_pricing && !$worksheet->show_calculation_summary) {
		$showSummary = false;
	}

	$lineTotal = $lineAmount = $totalPrice = $totalWithoutTax = $subTotal;
	$subTotalLabel = '';

	$lineTotal = $lineAmount += $noChargeAmt;
?>

@if(!$worksheet->multi_tier && !$worksheet->hide_pricing && ($worksheet->line_tax || $worksheet->line_margin_markup))
	<tr class="total-tier-root">
		<td class="tier-sr-number"></td>

		@if(!$worksheet->description_only)
			<td class="tier-type"></td>
			<td class="tier-name"></td>
			<td class="tier-price"></td>
		@endif

		@if($worksheet->description_only)
			@if($worksheet->show_unit && $worksheet->show_quantity)
				<td class="tier-price"></td>
			@else
				@if($worksheet->show_quantity)
					<td class="tier-qty"></td>
				@endif
				@if($worksheet->show_unit)
					<td class="tier-unit"></td>
				@endif
			@endif
		@endif

		<td class="tier-des" style="text-align: right;">Total</td>

		@if(!$worksheet->description_only)
		<td class="tier-total">{{ showAmount($lineAmount) }}</td>
		@endif

		@if($worksheet->line_tax)
			<?php
				$subTotalLabel = '(excluding Tax & Profit)';
				$totalLineTax = isset($worksheet->meta['total_line_tax']) ? $worksheet->meta['total_line_tax'] : 0;
				$lineTotal += $totalLineTax;
			?>
			@if(!$worksheet->description_only)
				<td class="tier-tax">
					{{ showAmount($totalLineTax) }}
				</td>
			@endif
		@endif
		@if($worksheet->line_margin_markup)
			<?php
				$subTotalLabel = '(excluding Tax & Profit)';
				$totalLineProfit = isset($worksheet->meta['total_line_profit']) ? $worksheet->meta['total_line_profit'] : 0;
				$lineTotal += $totalLineProfit;
			?>
			@if(!$worksheet->description_only)
				<td class="tier-profit">
					@if(isset($worksheet->meta['total_line_profit']))
					{{ showAmount($worksheet->meta['total_line_profit']) }}
					@endif
				</td>
			@endif
		@endif

		<td class="tier-total">{{ showAmount($lineTotal) }}</td>
	</tr>
@endif

<!-- claculation summary table -->
<table class="table table-striped bottom-table text-right border-top0" style="margin-bottom: 0; width: 60%; border: 1px solid #ddd; margin-top: 15px;">

	@if($showSummary && $noChargeAmt)
		<tr>
		 	<td width="30%">No Charge Amount </td>
		 	<td width="70%" colspan="2" class="ng-binding">- {{ showAmount($noChargeAmt) }}</td>
		</tr>
	@endif

	@if($worksheet->overhead
		|| $worksheet->profit
		|| $worksheet->taxable
		|| !is_null($worksheet->labor_tax_rate)
		|| !is_null($worksheet->commission)
		|| !is_null($worksheet->material_tax_rate)
		|| $worksheet->line_tax
		|| $worksheet->line_margin_markup
		|| $worksheet->fixed_price)
		@if($showSummary)
			<tr>
			 	<td width="30%">Subtotal{{ $subTotalLabel }} </td>
			 	<td width="70%" colspan="2" class="ng-binding">{{showAmount($subTotal)}}</td>
			</tr>
		@endif
	@endif

	@if($worksheet->update_tax_order)

		@if($worksheet->profit && (!$worksheet->show_line_total))
	 		<?php
	 			$profit = getWorksheetMarginMarkup($worksheet->margin, $subTotal, $worksheet->profit);
	 			$totalPrice 	 += $profit;
	 			$totalWithoutTax += $profit;
	 		?>

		 	@if($showSummary)
			 	<tr>
			 		<td>Projected Profit </td>
			 		<td>{{ numberFormat($worksheet->profit) }}%</td>
			 		<td class="ng-binding">{{ showAmount($profit) }}</td>
			 	</tr>
		 	@endif
	 	@elseif($worksheet->line_margin_markup && isset($worksheet->meta['total_line_profit']))
	 		<?php
	 			$totalPrice 	 += $worksheet->meta['total_line_profit'];
	 			$totalWithoutTax += $worksheet->meta['total_line_profit'];
	 		?>
			@if($showSummary && (!$worksheet->show_line_total))
				<tr>
					<td>Projected Profit </td>
					<td></td>
					<td class="ng-binding">{{ showAmount($worksheet->meta['total_line_profit']) }}</td>
				</tr>
			@endif
	 	@endif
	 	@if($worksheet->overhead)
			<?php
				$overhead 		 = calculateTax($subTotal, $worksheet->overhead);
				$totalPrice 	 += $overhead;
				$totalWithoutTax += $overhead;
			?>
			@if($showSummary)
				<tr>
					<td>Overhead </td>
					<td>{{ numberFormat($worksheet->overhead) }}%</td>
					<td class="ng-binding">{{ showAmount($overhead) }}</td>
				</tr>
			@endif
	 	@endif

		@if($worksheet->commission)
			<?php
				$commission = calculateTax($totalWithoutTax, $worksheet->commission);
				$totalPrice += $commission;
			?>
			@if($showSummary)
				<tr>
					<td>Commission </td>
					<td>{{ numberFormat($worksheet->commission) }}%</td>
					<td class="ng-binding">{{ showAmount($commission) }}</td>
				</tr>
			@endif
	 	@endif

	 	@if($worksheet->taxable && (!$worksheet->show_line_total))
	 		<?php $taxAmount = calculateTax($totalPrice, $worksheet->tax_rate); ?>
			@if($showSummary)
			 	<tr>
				 	<td>Tax(All) </td>
				 	<td>{{ numberFormat($worksheet->tax_rate, 3) }}%</td>
				 	<td class="ng-binding">{{ showAmount($taxAmount) }}</td>
				</tr>
			@endif
			<?php $totalPrice += $taxAmount; ?>
		@elseif($worksheet->line_tax && isset($worksheet->meta['total_line_tax']))
	 		<?php $totalPrice += $worksheet->meta['total_line_tax']; ?>
	 		@if($showSummary && (!$worksheet->show_line_total))
				<tr>
					<td>Tax</td>
					<td></td>
					<td class="ng-binding">{{ showAmount($worksheet->meta['total_line_tax']) }}</td>
				</tr>
			@endif
	 	@endif

		@if(!is_null($worksheet->material_tax_rate) && (!$worksheet->show_line_total))
		 	<?php
				if($worksheet->useSellingPrice()) {
					$materialTax = calculateTax($worksheet->meta['materials_selling_price_total'], $worksheet->material_tax_rate);
				} else {
					$materialTax = calculateTax($worksheet->meta['materials_cost_total'], $worksheet->material_tax_rate);
				}
		 	?>
	 		@if($showSummary)
		 		<tr>
				 	<td>Tax(Material) </td>
				 	<td>{{ numberFormat($worksheet->material_tax_rate, 3) }}%</td>
				 	<td class="ng-binding">{{ showAmount($materialTax) }}</td>
				 </tr>
			@endif
			<?php $totalPrice += $materialTax; ?>
	 	@endif

	 	@if(!is_null($worksheet->labor_tax_rate))
		 	<?php
				if($worksheet->useSellingPrice()) {
					$laborTax = calculateTax($worksheet->meta['labor_selling_price_total'], $worksheet->labor_tax_rate);
				} else {
					$laborTax = calculateTax($worksheet->meta['labor_cost_total'], $worksheet->labor_tax_rate);
				}
		 	?>
	 		@if($showSummary)
		 		<tr>
				 	<td>Tax(Labor) </td>
				 	<td>{{ numberFormat($worksheet->labor_tax_rate, 3) }}%</td>
				 	<td class="ng-binding">{{ showAmount($laborTax) }}</td>
				 </tr>
			@endif
			<?php $totalPrice += $laborTax; ?>
	 	@endif
	@else
		@if(!is_null($worksheet->material_tax_rate) && (!$worksheet->show_line_total))
		 	<?php
				if($worksheet->useSellingPrice()) {
					$materialTax = calculateTax($worksheet->meta['materials_selling_price_total'], $worksheet->material_tax_rate);
				} else {
					$materialTax = calculateTax($worksheet->meta['materials_cost_total'], $worksheet->material_tax_rate);
				}
		 	?>
	 		@if($showSummary)
		 		<tr>
				 	<td>Tax(Material) </td>
				 	<td>{{ numberFormat($worksheet->material_tax_rate, 3) }}%</td>
				 	<td class="ng-binding">{{ showAmount($materialTax) }}</td>
				 </tr>
			@endif
			<?php $totalPrice += $materialTax; ?>
	 	@endif

	 	@if(!is_null($worksheet->labor_tax_rate) && (!$worksheet->show_line_total))
		 	<?php
				if($worksheet->useSellingPrice()) {
					$laborTax = calculateTax($worksheet->meta['labor_selling_price_total'], $worksheet->labor_tax_rate);
				} else {
					$laborTax = calculateTax($worksheet->meta['labor_cost_total'], $worksheet->labor_tax_rate);
				}
		 	?>
	 		@if($showSummary)
		 		<tr>
				 	<td>Tax(Labor) </td>
				 	<td>{{ numberFormat($worksheet->labor_tax_rate, 3) }}%</td>
				 	<td class="ng-binding">{{ showAmount($laborTax) }}</td>
				 </tr>
			@endif
			<?php $totalPrice += $laborTax; ?>
	 	@endif

	 	@if($worksheet->taxable && (!$worksheet->show_line_total))
	 		<?php $taxAmount = calculateTax($subTotal, $worksheet->tax_rate); ?>
			@if($showSummary)
			 	<tr>
				 	<td>Tax(All) </td>
				 	<td>{{ numberFormat($worksheet->tax_rate, 3) }}%</td>
				 	<td class="ng-binding">{{ showAmount($taxAmount) }}</td>
				</tr>
			@endif
			<?php $totalPrice += $taxAmount; ?>
		@elseif($worksheet->line_tax && isset($worksheet->meta['total_line_tax']))
	 		<?php $totalPrice += $worksheet->meta['total_line_tax']; ?>
	 		@if($showSummary)
				<tr>
					<td>Tax</td>
					<td></td>
					<td class="ng-binding">{{ showAmount($worksheet->meta['total_line_tax']) }}</td>
				</tr>
			@endif
	 	@endif

		@if($worksheet->overhead)
			<?php
				$overhead 		 = calculateTax($subTotal, $worksheet->overhead);
				$totalPrice 	 += $overhead;
				$totalWithoutTax += $overhead;
			?>
			@if($showSummary)
				<tr>
					<td>Overhead </td>
					<td>{{ numberFormat($worksheet->overhead) }}%</td>
					<td class="ng-binding">{{ showAmount($overhead) }}</td>
				</tr>
			@endif
	 	@endif

	 	@if($worksheet->profit && (!$worksheet->show_line_total))
	 		<?php
	 			$profit = getWorksheetMarginMarkup($worksheet->margin, $subTotal, $worksheet->profit);
	 			$totalPrice 	 += $profit;
	 			$totalWithoutTax += $profit;
	 		?>

		 	@if($showSummary)
			 	<tr>
			 		<td>Projected Profit </td>
			 		<td>{{ numberFormat($worksheet->profit) }}%</td>
			 		<td class="ng-binding">{{ showAmount($profit) }}</td>
			 	</tr>
		 	@endif
	 	@elseif($worksheet->line_margin_markup && isset($worksheet->meta['total_line_profit']))
	 		<?php
	 			$totalPrice 	 += $worksheet->meta['total_line_profit'];
	 			$totalWithoutTax += $worksheet->meta['total_line_profit'];
	 		?>
			@if($showSummary && (!$worksheet->show_line_total))
				<tr>
					<td>Projected Profit </td>
					<td></td>
					<td class="ng-binding">{{ showAmount($worksheet->meta['total_line_profit']) }}</td>
				</tr>
			@endif
	 	@endif

		@if($worksheet->commission)
			<?php
				$commission = calculateTax($totalWithoutTax, $worksheet->commission);
				$totalPrice += $commission;
			?>
			@if($showSummary)
				<tr>
					<td>Commission </td>
					<td>{{ numberFormat($worksheet->commission) }}%</td>
					<td class="ng-binding">{{ showAmount($commission) }}</td>
				</tr>
			@endif
	 	@endif
	@endif

	@if($worksheet->fixed_price)
		<?php
			$profitLoss = ($worksheet->fixed_price - $totalPrice);
			$totalPrice += $profitLoss;
		?>
		@if($showSummary)
			<tr>
				<td>Profit/Loss</td>
				@if($profitLoss < 0)
					<td class="ng-binding" style="color: red" colspan="2">{{ showAmount($profitLoss) }}</td>
				@else
					<td class="ng-binding" colspan="2">{{ showAmount($profitLoss) }}</td>
				@endif
			</tr>
		@endif
 	@endif

 	@if($showSummary || in_array($worksheet->type, [App\Models\Worksheet::PROPOSAL, App\Models\Worksheet::ESTIMATE]))
		<tr>
		 	<td>Total </td>
		 	<td colspan="2" class="ng-binding">{{showAmount($totalPrice)}}</td>
		</tr>
	@endif
</table>

