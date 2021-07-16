<tr class="tier-head-color">
	@if(in_array('serial_number', $printFields))
		<th class="tier-sr-number">#</th>
	@endif

	@if(in_array('type', $printFields))
		<th class="tier-type">Type</th>
	@endif

	@if(in_array('name', $printFields))
		<th class="tier-name">Name</th>
	@endif

	@if(in_array('description', $printFields))
		<th class="tier-des">Description</th>
			@if(in_array('add_des_column', $printFields))
				<th class="tier-total"></th>
			@endif
	@endif

	@if(in_array('price_unit_qty', $printFields))
		<th class="tier-price">Price / Unit / Qty</th>
	@elseif(in_array('unit_qty', $printFields))
		<th class="tier-price">Unit / Qty</th>
	@else
		@if(in_array('only_qty', $printFields))
			<th class="tier-qty">Qty</th>
		@endif
		@if(in_array('only_unit', $printFields))
			<th class="tier-unit">Unit</th>
		@endif
	@endif

	@if(in_array('line_amount', $printFields))
		<th class="tier-total">Line Amount</th>
	@endif

	@if(in_array('line_tax', $printFields))
		<th class="tier-tax">Tax %</th>
	@endif

	@if(in_array('line_margin_markup', $printFields))

		<?php $lineMarginLabel = $worksheetMargin ? 'Margin' : 'Markup'; ?>

		<th class="tier-profit">Profit ({{ $lineMarginLabel }})</th>
	@endif

	@if(in_array('line_total', $printFields))
		<th class="tier-total">Line Total</th>
	@endif
</tr>