@foreach($financial_details as $detail)
	<div class="tiers tier1 root-tier">
		<div class="phase-item-feilds">
			<span class="tier-des">
				<div class="phase-bg-color white-bg">
					<div class="print-text-field">
						{{ $detail->description }}
					</div>
				</div>
			</span>

			<span class="tier-qty">
				<div class="phase-bg-color white-bg">
					<div class="print-text-field">
						{{ moneyFormat($detail->quantity) }}
					</div>
				</div>
			</span>

			<span class="tier-unit">
				<div class="phase-bg-color white-bg">
					<div class="print-text-field">
						{{ $detail->unit }}
					</div>
				</div>
			</span>

			<span class="tier-price">
				<div class="phase-bg-color white-bg">
					<div class="print-text-field">
						{{ currencyFormat($detail->unit_cost) }}
					</div>
				</div>
			</span>

			<span class="tier-tax">
				<div class="phase-bg-color white-bg">
					<div class="print-text-field">
						{{ currencyFormat($detail->tax) }}
					</div>
				</div>
			</span>

			<span class="tier-rcv">
				<div class="phase-bg-color white-bg">
					<div class="print-text-field">
						{{ currencyFormat($detail->rcv) }}
					</div>
				</div>
			</span>

			<span class="tier-depct">
				<div class="phase-bg-color white-bg">
					<div class="print-text-field">
						{{ currencyFormat($detail->depreciation) }}
					</div>
				</div>
			</span>

			<span class="tier-acv">
				<div class="phase-bg-color white-bg">
					<div class="print-text-field">
						{{ currencyFormat($detail->acv) }}
					</div>
				</div>
			</span>
		</div>
	</div>
@endforeach