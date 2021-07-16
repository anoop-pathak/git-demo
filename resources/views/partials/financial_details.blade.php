<?php
	$count = 0;
	$colSpan = count($printFields);
	if(in_array('line_total', $printFields)) $colSpan -= 1;
	if(in_array('hide_tier_pricing', $printFields)) $colSpan -= 2;
	if(in_array('add_des_column', $printFields)) $colSpan -= 1;
	if(in_array('others', $printFields)) $colSpan -= 1;
	if(in_array('no_hide_pricing', $printFields) && in_array('line_total', $printFields)) $colSpan -= 2;
	if(!in_array('hide_tier_pricing', $printFields) && in_array('line_total', $printFields)) $colSpan += 1;
	if(in_array('hide_tier_pricing', $printFields) && in_array('line_total', $printFields)) $colSpan += 1;
?>

@foreach($financial_details as $detail)

	@if($detail->type == 'item')
		<?php $setting = $detail->data->setting; ?>

		@if(!isset($tier_data))
		<tr class="root-tier">
		@elseif(isset($content_class))
		<tr class="tier-content{{ $content_class }}">
		@endif
		<!-- hide line items if collapse line items true -->
		@if((!$worksheet->collapse_all_line_items && (!isset($tier_collapse))) || ((isset($tier_collapse) && !$tier_collapse)))
		<div class="phase-item-feilds">
			@if(in_array('serial_number', $printFields))
				<td class="tier-sr-number">
					<div class="tier-inner">{{ ++$count }}</div>
				</td>
			@endif

			@if(in_array('type', $printFields))
				<td class="tier-type">
					@if(!ine($setting, 'description_only'))
						<div class="tier-inner">{{ $categoryName = $detail->data->category->name }}</div>
					@endif
				</td>
			@endif
			@if(in_array('name', $printFields))
				<td class="tier-name">
					<div class="tier-inner">{{ $detail->data->product_name }}</div>
				</td>
			@endif

			@if(in_array('description', $printFields))
			<?php
				$desColSpan = 0;
				if((!$worksheet->description_only) && ine($setting, 'description_only')) {
					$desColSpan = 2;
					if(!$worksheet->hide_pricing && ($worksheet->line_tax || $worksheet->line_margin_markup)) {
						$desColSpan += 1;
						if($worksheet->line_tax) {
							$desColSpan += 1;
						}
						if($worksheet->line_margin_markup) {
							$desColSpan += 1;
						}
					}
				}
				?>
				<td class="tier-des" colspan="{{$desColSpan}}">
					<div class="tier-inner">
						<span class="des">@if(!in_array('name', $printFields))<label class="text-bold">{{ $detail->data->product_name }}</label><br>@endif{{ $detail->data->description }}</span>

						<?php
							$label = [];
							$content = [];
							if(!empty($detail->data->style)) {
								$label[]   = 'Type/Style(s)';
								$content[] = $detail->data->style;
							}
							if(!empty($detail->data->size)) {
								$label[]   = 'Size';
								$content[] = $detail->data->size;
							}
							if(!empty($detail->data->color)) {
								$label[]   = 'Color';
								$content[] = $detail->data->color;
							}
						?>
						@if((in_array('others', $printFields) || (ine($setting, 'description_only')) || ($worksheet->description_only))
							&& ((!empty($label) 
								&& !empty($content)) 
								|| $detail->data->trade 
								|| $detail->data->workType
								|| $detail->data->supplier))

							<div class="tier-extra-fields">
								@if($detail->data->supplier)
								<div class="tier-field-block">
									<label for="">Supplier: </label>
									<span>{{ $detail->data->supplier->name }}</span>
								</div>
								@endif
								@if($detail->data->trade)
								<div class="tier-field-block">
									<label for="">Trade Type: </label>
									<span>{{ $detail->data->trade->name }}</span>
								</div>
								@endif
								@if($detail->data->workType)
								<div class="tier-field-block">
									<label for="">Work Type: </label>
									<span>{{ $detail->data->workType->name }}</span>
								</div>
								@endif
								@if(!empty($label) && !empty($content))
								<div class="tier-field-block">
									<label for="">{{ implode(',', $label) }}: </label>
									<span> {{ implode(', ', $content) }}</span>
								</div>
								@endif
							</div>
						@endif
					</div>
				</td>
				@if(in_array('add_des_column', $printFields))
					@if(!ine($setting, 'description_only'))
						<td>
							<div class="tier-inner"></div>
						</td>
					@endif
				@endif
			@endif

			<?php
				if($worksheet->useSellingPrice()) {

					$cost = $detail->data->selling_price;
				}else{

					$cost = $detail->data->unit_cost;
				}

				$lineTotal = $subTotal = $lineAmount = $cost * $detail->data->quantity;
				?>

			@if(in_array('price_unit_qty', $printFields))
				@if(!ine($setting, 'description_only') || ($worksheet->description_only))
					<td class="tier-price">
						<div class="tier-inner">({{currencyFormat($cost)}} / {{ $detail->data->unit }}) x {{ $detail->data->quantity }}</div>
					</td>
				@endif
			@elseif(in_array('unit_qty', $printFields))
				@if(!ine($setting, 'description_only') || ($worksheet->description_only))
					<td class="tier-price">
						<div class="tier-inner">{{ $detail->data->unit }} / {{ $detail->data->quantity }}</div>
					</td>
				@endif
			@else
				@if(in_array('only_qty', $printFields))
					@if(!ine($setting, 'description_only') || ($worksheet->description_only))
						<td class="tier-qty">
							<div class="tier-inner">{{ $detail->data->quantity }}</div>
						</td>
					@endif
				@endif
				@if(in_array('only_unit', $printFields))
					@if(!ine($setting, 'description_only') || ($worksheet->description_only))
					<td class="tier-unit">
						<div class="tier-inner">{{ $detail->data->unit }}</div>
					</td>
					@endif
				@endif
			@endif

			@if(in_array('line_amount', $printFields))
				@if(!ine($setting, 'description_only'))
					<td class="tier-total">
						<div class="tier-inner">{{ showAmount($lineAmount) }}</div>
					</td>
				@endif
			@endif

			@if($worksheet->line_tax)
				<?php $lineTotal += $tax = calculateTax($subTotal, $detail->data->line_tax); ?>
				@if(in_array('line_tax', $printFields))
					@if(!ine($setting, 'description_only'))
						<td class="tier-tax">
							<div class="tier-inner">{{ showAmount($tax) }} ({{ numberFormat($detail->data->line_tax) }}%)</div>
						</td>
					@endif
				@endif
			@endif

			@if($worksheet->line_margin_markup)
				<?php
					$lineTotal += $profit = getWorksheetMarginMarkup(
						$worksheet->margin,
						$subTotal,
						$detail->data->line_profit
					);
				?>
				@if(in_array('line_margin_markup', $printFields))
					@if(!ine($setting, 'description_only'))
						<td class="tier-profit">
							<div class="tier-inner">{{ showAmount($profit) }} ({{ numberFormat($detail->data->line_profit) }}%)</div>
						</td>
					@endif
				@endif
			@endif

			@if(in_array('line_total', $printFields))
				<td class="tier-total">
					<div class="tier-inner">
						@if(!is_null($lineTotal))
							{{ showAmount($lineTotal) }}
						@else 
							--
						@endif
					</div>
				</td>
			@endif
		</div>
		@endif
		@if(!isset($tier_data) || isset($content_class))
		</tr>
		@endif
	@else
		<?php
			$colSpanDes = $colSpan;
			$colSpanName = 2;
			if(!in_array('name', $printFields)) {
				$colSpanName = 1;
			}
			$colSpanDes = $colSpanDes - $colSpanName;
			if($worksheet->description_only && (($worksheet->show_quantity || $worksheet->show_unit) || ($worksheet->hide_pricing && $worksheet->show_line_total))) {
				$colSpanDes += 1;
			}
		?>
		<tr class="tiers tier{{ $detail->tier }}">
			@if(in_array('name', $printFields))
				<td colspan="{{ $colSpan }}">
					<div class="tier-inner">{{ $detail->tier_name }} {{ $detail->tier_description }}</div>
				</td>
			@endif
			<td colspan="{{ $colSpanDes }}">
				<div class="tier-inner">
					<span>@if(!in_array('name', $printFields))<label class="text-bold">{{ $detail->tier_name }}</label><br>@endif{{ $detail->tier_description }}</span>
				</div>
			</td>
			@if(!$worksheet->hide_pricing || ($worksheet->hide_pricing && ($worksheet->show_tier_total)))
				<td class="tier-total">
					<div class="tier-inner">{{ showAmount($detail->sum) }}</div>
				</td>
			@endif
		</tr>

		@include('partials.financial_details', [
			'financial_details' => $detail->data,
			'worksheet'			=> $worksheet,
			'tier_data' 		=> true,
			'content_class' 	=> $detail->tier,
			'tier_collapse' 	=> $detail->tier_collapse,
		])
	@endif
@endforeach