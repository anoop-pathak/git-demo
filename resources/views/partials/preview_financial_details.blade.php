<?php
	$count = 0;
	$colSpan = count($printFields);

	if(in_array('no_hide_pricing', $printFields) && in_array('line_total', $printFields)) $colSpan -= 2;
	if(!in_array('hide_tier_pricing', $printFields) && in_array('line_total', $printFields)) $colSpan += 1;
	if(in_array('hide_tier_pricing', $printFields) && in_array('line_total', $printFields)) $colSpan += 1;
	if(in_array('line_total', $printFields)) $colSpan -= 1;
	if(in_array('others', $printFields)) $colSpan -= 1;
	if(in_array('hide_tier_pricing', $printFields)) $colSpan -= 2;
	if(in_array('add_des_column', $printFields)) $colSpan -= 1;
?>

@foreach($financial_details as $detail)
	<?php
		$detailData = $detail->data;
		$setting = isset($detailData->setting) ? $detailData->setting : [];
	?>

	@if($detail->type == 'item')
		@if(!isset($tier_data))
		<tr class="root-tier">
		@elseif(isset($content_class))
		<tr class="tier-content{{ $content_class }}">
		@endif
		<!-- hide line items if collapse line items true -->
		@if((!$collapse_all_line_items && !isset($tier_collapse)) || (isset($tier_collapse) && !$tier_collapse))
			<div class="phase-item-feilds">
				@if(in_array('serial_number', $printFields))
					<td class="tier-sr-number">
						<div class="tier-inner">{{ ++$count }}</div>
					</td>
				@endif

				@if(in_array('type', $printFields))
					<td class="tier-type">
						<div class="tier-inner">{{ $detail->data->category_name ?? '' }}</div>
					</td>
				@endif
				@if(in_array('name', $printFields))
					<td class="tier-name">
						<div class="tier-inner">{{ $detail->data->product_name ?? '' }}</div>
					</td>
				@endif

				@if(in_array('description', $printFields))
					<?php
						$desColSpan = 0;
						if((!$description_only) && ine($setting, 'description_only')) {
							$desColSpan = 2;
							if(!$hide_pricing && ($enable_line_tax || $enable_line_margin)) {
								$desColSpan += 1;
								if($enable_line_tax) {
									$desColSpan += 1;
								}
								if($enable_line_margin) {
									$desColSpan += 1;
								}
							}
						}
					?>
					<td class="tier-des" colspan="{{$desColSpan}}">
						<div class="tier-inner">
							<span class="des">@if(!in_array('name', $printFields))<label class="text-bold">{{ $detail->data->product_name ?? ''}}</label><br>@endif{{ $detail->data->description ?? '' }}</span>
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
							@if((in_array('others', $printFields) || (ine($setting, 'description_only')) || ($description_only))
								&& ((!empty($label) 
									&& !empty($content)) 
									|| isset($detail->data->trade_name)
									|| isset($detail->data->work_type_name)
									|| isset($detail->data->supplier)))
								<div class="tier-extra-fields">
									@if(isset($detail->data->supplier))
									<div class="tier-field-block">
										<label for="">Supplier: </label>
										<span>{{ $detail->data->supplier->name }}</span>
									</div>
									@endif
									@if(isset($detail->data->trade_name))
									<div class="tier-field-block">
										<label for="">Trade Type: </label>
										<span>{{ $detail->data->trade_name }}</span>
									</div>
									@endif
									@if(isset($detail->data->work_type_name))
									<div class="tier-field-block">
										<label for="">Work Type: </label>
										<span>{{ $detail->data->work_type_name }}</span>
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
						<td>
							<div class="tier-inner"></div>
						</td>
					@endif
				@endif

				<?php
					if($enable_selling_price) {

						$cost = $detail->data->selling_price;
					}else{

						$cost = $detail->data->unit_cost;
					}

					$lineTotal = $subTotal = $lineAmount = $cost * $detail->data->quantity;
				?>

				@if(in_array('price_unit_qty', $printFields))
					@if(!ine($setting, 'description_only'))
						<td class="tier-price">
							<div class="tier-inner">({{currencyFormat($cost)}} / {{ $detail->data->unit }}) x {{ $detail->data->quantity }}</div>
						</td>
					@endif
				@elseif(in_array('unit_qty', $printFields))
					@if(!ine($setting, 'description_only'))
						<td class="tier-price">
							<div class="tier-inner">
								{{ $detail->data->unit or '' }} / {{ $detail->data->quantity or '' }}
							</div>
						</td>
					@endif
				@else
					@if(in_array('only_qty', $printFields))
						@if(!ine($setting, 'description_only'))
							<td class="tier-qty">
								<div class="tier-inner">{{ $detail->data->quantity or '' }}</div>
							</td>
						@endif
						@if(in_array('only_unit', $printFields))
							<td class="tier-unit">
								<div class="tier-inner">{{ $detail->data->unit or '' }}</div>
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

				@if($enable_line_tax)
					<?php
						$lineTax = isset($detail->data->line_tax) ? $detail->data->line_tax : 0;
						$lineTotal += $tax = calculateTax($subTotal, $lineTax);
					?>
					@if(in_array('line_tax', $printFields))
						@if(!ine($setting, 'description_only'))
							<td class="tier-tax">
								<div class="tier-inner">{{ showAmount($tax) }} ({{ numberFormat($lineTax) }}%)</div>
						@endif
					@endif
				@endif

				@if($enable_line_margin)
					<?php
						$lineProfit = isset($detail->data->line_profit) ? $detail->data->line_profit : 0;
						$lineTotal += $profit = getWorksheetMarginMarkup(
							$margin,
							$subTotal,
							$lineProfit
						);
					?>
					@if(in_array('line_margin_markup', $printFields))
						@if(!ine($setting, 'description_only'))
							<td class="tier-profit">
								<div class="tier-inner">{{ showAmount($profit) }} ({{ numberFormat($lineProfit) }}%)</div>
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
			if($description_only && (($show_quantity || $show_unit) || ($hide_pricing && $show_line_total))) {
				$colSpanDes += 1;
			}
		?>
		@if(ine($column_settings, 'show_tier_color'))
			<tr class="tiers tier{{ $detail->tier }}">
		@else
			<tr class="tiers tier{{ $detail->tier }} tier-transparent">
		@endif
			@if(in_array('name', $printFields))
			<td colspan="{{ $colSpanName }}">
				<div class="tier-inner">{{ $detail->tier_name }}</div>
			</td>
			@endif
			<td colspan="{{ $colSpanDes }}">
				<div class="tier-inner">
					<span class="tier-item-description">@if(!in_array('name', $printFields))<label class="text-bold tier-title-color">{{ $detail->tier_name }}</label><br>@endif{{ $detail->tier_description }}</span>
				</div>
			</td>

			@if(!$hide_pricing || ($hide_pricing && $show_tier_total))
				<td class="tier-total">
					<div class="tier-inner">{{ showAmount($detail->sum) }}</div>
				</td>
			@endif
		</tr>

		@include('partials.preview_financial_details', [
			'financial_details'		=> $detail->data,
			'enable_selling_price'	=> $enable_selling_price,
			'tier_data'				=> true,
			'content_class' 		=> $detail->tier,
			'tier_collapse'			=> $detail->tier_collapse,
		])
	@endif
@endforeach