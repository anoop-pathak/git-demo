@foreach($financial_details as $detail)

	<div class="tiers tier1 root-tier">
		<div class="phase-item-feilds">
			<span class="tier-des">
				<div class="phase-bg-color white-bg">
					<div class="print-text-field">
						{{ $detail['description'] }}
					</div>
				</div>
			</span>

			<span class="tier-qty">
				<div class="phase-bg-color white-bg">
					<div class="print-text-field">
						{{ moneyFormat($detail['quantity']) }}
					</div>
				</div>
			</span>

			<span class="tier-unit">
				<div class="phase-bg-color white-bg">
					<div class="print-text-field">
						{{ $detail['unit'] }}
					</div>
				</div>
			</span>

			<span class="tier-price">
				<div class="phase-bg-color white-bg">
					<div class="print-text-field">
						{{ currencyFormat($detail['unit_cost']) }}
					</div>
				</div>
			</span>

			<span class="tier-tax">
				<div class="phase-bg-color white-bg">
					<div class="print-text-field">
						{{ isset($detail['tax']) ? currencyFormat($detail['tax']) : currencyFormat(0)}}
					</div>
				</div>
			</span>

			<span class="tier-rcv">
				<div class="phase-bg-color white-bg">
					<div class="print-text-field">
						{{ isset($detail['rcv']) ? currencyFormat($detail['rcv']) : currencyFormat(0)}}
					</div>
				</div>
			</span>

			<span class="tier-depct">
				<div class="phase-bg-color white-bg">
					<div class="print-text-field">
						{{ isset($detail['depreciation']) ? currencyFormat($detail['depreciation']) : currencyFormat(0)}}
					</div>
				</div>
			</span>

			<span class="tier-acv">
				<div class="phase-bg-color white-bg">
					<div class="print-text-field">
						{{ isset($detail['acv']) ? currencyFormat($detail['acv']) : currencyFormat(0)}}
					</div>
				</div>
			</span>
		</div>
	</div>
@endforeach

<!-- calculation summary table -->
<table class="table table-striped bottom-table text-right" style="margin-bottom: 0; width: 45%; border: 1px solid #ddd;">

	<tr>
		<td>Total Tax </td>
		<td colspan="2" class="ng-binding">
			{{ currencyFormat($financial_details->sum('tax')) }}
		</td>
	</tr>

	<tr>
		<td>Total RCV </td>
		<td colspan="2" class="ng-binding">
			{{ currencyFormat($financial_details->sum('rcv')) }}
		</td>
	</tr>

	<tr>
		<td>Depreciation </td>
		<td colspan="2" class="ng-binding">
			{{ currencyFormat($financial_details->sum('depreciation')) }}
		</td>
	</tr>

	<tr>
		<td>Total ACV </td>
		<td colspan="2" class="ng-binding">
			{{ currencyFormat($financial_details->sum('acv')) }}
		</td>
	</tr>
</table>