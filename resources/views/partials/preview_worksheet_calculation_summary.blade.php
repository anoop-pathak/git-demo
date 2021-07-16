<?php
	$subTotalLabel = '';
	$showSummary = true;

	if($hide_pricing && !$show_calculation_summary) {
		$showSummary = false;
	}
?>

@if(!$multi_tier && !$hide_pricing && ($enable_line_tax || $enable_line_margin))
	<tr class="total-tier-root">
		<td class="tier-sr-number"></td>

		@if(!$description_only)
			<td class="tier-type"></td>
			<td class="tier-name"></td>
			<td class="tier-price"></td>
		@endif

		@if($description_only)
			@if($show_unit && $show_quantity)
				<td class="tier-price"></td>
			@else
				@if($show_quantity)
					<td class="tier-qty"></td>
				@endif
				@if($show_unit)
					<td class="tier-unit"></td>
				@endif
			@endif
		@endif

		<td class="tier-des" style="text-align: right;">Total</td>

		@if(!$description_only)
		<td class="tier-total">{{ showAmount($line_amount) }}</td>
		@endif

		@if($enable_line_tax)
			<?php
				$subTotalLabel = '(excluding Tax & Profit)';
				$line_amount += $total_line_tax;
			?>
			@if(!$description_only)
				<td class="tier-tax">
					{{ showAmount($total_line_tax) }}
				</td>
			@endif
		@endif
		@if($enable_line_margin)
			<?php
				$subTotalLabel = '(excluding Tax & Profit)';
				$line_amount += $total_line_profit;
			?>
			@if(!$description_only)
				<td class="tier-profit">
					{{ showAmount($total_line_profit) }}
				</td>
			@endif
		@endif

		<td class="tier-total">{{ showAmount($line_amount) }}</td>
	</tr>
@endif

<!-- calculation summary table -->
<table class="table table-striped bottom-table text-right border-top0" style="margin-bottom: 0; width: 60%; border: 1px solid #ddd; margin-top: 15px;">

	@if(!$showSummary && $no_charge_amount)
		<tr>
		 	<td width="30%">No Charge Amount </td>
		 	<td width="70%" colspan="2" class="ng-binding">- {{ showAmount($no_charge_amount) }}</td>
		</tr>
	@endif

	@if($overhead
		|| $profit
		|| $tax_rate
		|| $labor_tax_rate
		|| $material_tax_rate
		|| $commission
		|| $enable_line_tax
		|| $enable_line_margin
		|| (!is_null($profit_loss)))
		@if(!$showSummary)
			<tr>
			 	<td width="30%">Subtotal{{ $subTotalLabel }} </td>
			 	<td width="70%" colspan="2" class="ng-binding">{{showAmount($sub_total)}}</td>
			</tr>
		@endif
	@endif

	@if($update_tax_order)

		@if($profit && (!$show_line_total))
	 		<?php $totalProfit = getWorksheetMarginMarkup($margin, $sub_total, $profit); ?>
			@if(!$showSummary)
	 			<tr>
				 	<td>Projected Profit </td>
				 	<td>{{ numberFormat($profit) }}%</td>
				 	<td class="ng-binding">{{ showAmount($totalProfit) }}</td>
				</tr>
			@endif
	 	@elseif($enable_line_margin && (!$show_line_total))
	 		@if(!$showSummary)
				<tr>
					<td>Projected Profit </td>
					<td></td>
					<td class="ng-binding">{{ showAmount($total_line_profit) }}</td>
				</tr>
			@endif
		@endif

		@if($overhead)
			<?php 
				$totalOverhead = calculateTax($sub_total, $overhead);
			?>

			@if(!$showSummary)
				<tr>
					<td>Overhead </td>
					<td>{{ numberFormat($overhead) }}%</td>
					<td class="ng-binding">{{ showAmount($totalOverhead) }}</td>
				</tr>
			@endif
	 	@endif

		@if($commission)
			<?php $totalCommission = calculateTax($total_without_tax, $commission); ?>
			@if(!$showSummary)
				<tr>
					<td>Commission </td>
					<td>{{ numberFormat($commission) }}%</td>
					<td class="ng-binding">{{ showAmount($totalCommission) }}</td>
				</tr>
			@endif
	 	@endif

	 	@if($tax_rate && (!$show_line_total))
		 	@if(!$showSummary)
		 	<tr>
			 	<td>Tax(All) </td>
			 	<td>{{ numberFormat($tax_rate, 3) }}%</td>
			 	<td class="ng-binding">{{ showAmount($tax_amount) }}</td>
			</tr>
			@endif
		@elseif($enable_line_tax && (!$show_line_total))
			@if(!$showSummary)
				<tr>
					<td>Tax </td>
					<td></td>
					<td class="ng-binding">{{ showAmount($total_line_tax) }}</td>
				</tr>
			@endif
		@endif

		@if(!is_null($material_tax_rate) && (!$show_line_total))
			@if(!$showSummary)
		 		<tr>
				 	<td>Tax(Material) </td>
				 	<td>{{ numberFormat($material_tax_rate, 3) }}%</td>
				 	<td class="ng-binding">{{ showAmount($materialTax = calculateTax($materials_cost, $material_tax_rate)) }}</td>
				 </tr>
			 @endif
	 	@endif

	 	@if(!is_null($labor_tax_rate) && (!$show_line_total))
	 		@if(!$showSummary)
		 		<tr>
				 	<td>Tax(Labor) </td>
				 	<td>{{ numberFormat($labor_tax_rate, 3) }}%</td>
				 	<td class="ng-binding">{{ showAmount($laborTax = calculateTax($labors_cost, $labor_tax_rate)) }}</td>
				 </tr>
			@endif
	 	@endif
	@else

	 	@if(!is_null($material_tax_rate) && (!$show_line_total))
			@if(!$showSummary)
		 		<tr>
				 	<td>Tax(Material) </td>
				 	<td>{{ numberFormat($material_tax_rate, 3) }}%</td>
				 	<td class="ng-binding">{{ showAmount($materialTax = calculateTax($materials_cost, $material_tax_rate)) }}</td>
				 </tr>
			 @endif
	 	@endif

	 	@if(!is_null($labor_tax_rate) && (!$show_line_total))
	 		@if(!$showSummary)
		 		<tr>
				 	<td>Tax(Labor) </td>
				 	<td>{{ numberFormat($labor_tax_rate, 3) }}%</td>
				 	<td class="ng-binding">{{ showAmount($laborTax = calculateTax($labors_cost, $labor_tax_rate)) }}</td>
				 </tr>
			@endif
	 	@endif

	 	@if($tax_rate && (!$show_line_total))
	 	<?php $taxAmount = calculateTax($sub_total, $tax_rate); ?>
		 	@if(!$showSummary)
		 	<tr>
			 	<td>Tax(All) </td>
			 	<td>{{ numberFormat($tax_rate, 3) }}%</td>
			 	<td class="ng-binding">{{ showAmount($taxAmount) }}</td>
			</tr>
			@endif
		@endif

		@if($enable_line_tax && (!$show_line_total))
			@if(!$showSummary)
				<tr>
					<td>Tax </td>
					<td></td>
					<td class="ng-binding">{{ showAmount($total_line_tax) }}</td>
				</tr>
			@endif
		@endif

		@if($overhead)
			<?php
				$totalOverhead = calculateTax($sub_total, $overhead);
			?>

			@if(!$showSummary)
				<tr>
					<td>Overhead </td>
					<td>{{ numberFormat($overhead) }}%</td>
					<td class="ng-binding">{{ showAmount($totalOverhead) }}</td>
				</tr>
			@endif
	 	@endif

	 	@if($profit && (!$show_line_total))

	 	<?php $totalProfit = getWorksheetMarginMarkup($margin, $sub_total, $profit); ?>
			@if(!$showSummary)
	 			<tr>
				 	<td>Projected Profit </td>
				 	<td>{{ numberFormat($profit) }}%</td>
				 	<td class="ng-binding">{{ showAmount($totalProfit) }}</td>
				</tr>
			@endif
	 	@endif

	 	@if($enable_line_margin && (!$show_line_total))
	 		@if(!$showSummary)
				<tr>
					<td>Projected Profit </td>
					<td></td>
					<td class="ng-binding">{{ showAmount($total_line_profit) }}</td>
				</tr>
			@endif
		@endif

		@if($commission)
			<?php $totalCommission = calculateTax($total_without_tax, $commission); ?>
			@if(!$showSummary)
				<tr>
					<td>Commission </td>
					<td>{{ numberFormat($commission) }}%</td>
					<td class="ng-binding">{{ showAmount($totalCommission) }}</td>
				</tr>
			@endif
	 	@endif

	@endif

	@if(!is_null($profit_loss))
 		@if($showSummary)
	 		<tr>
			 	<td>Profit/Loss </td>
			 	@if($profit_loss < 0)
			 		<td class="ng-binding" style="color: red" colspan="2">{{ showAmount($profit_loss) }}</td>
			 	@else
			 		<td class="ng-binding" colspan="2">{{ showAmount($profit_loss) }}</td>
			 	@endif
			 </tr>
		@endif
 	@endif

	@if(!$showSummary || in_array($type, [Worksheet::PROPOSAL, Worksheet::ESTIMATE]))
		<tr>
		 	<td>Total </td>
		 	<td colspan="2" class="ng-binding">{{showAmount($total_amount)}}</td>
		</tr>
	@endif
</table>