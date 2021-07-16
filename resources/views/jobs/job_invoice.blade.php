<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title> JobProgress </title>
	<link rel="stylesheet" href="{{ config('app.url') }}css/base.css">
	<link rel="stylesheet" href="{{ config('app.url') }}css/print-header.css">
	<link rel="stylesheet" href="{{ config('app.url') }}css/print-table.css">
</head>
<body>
	<?php $updatedDate = \Carbon\Carbon::parse($invoice->updated_at, \Settings::get('TIME_ZONE'))->format(config('jp.date_format')); ?>
	<div class="print-main-wrapper">
		<div class="print-header-outer">
			<div class="print-header-wrap">
				<p class="print-modified">Last Modified: {{ $updatedDate }}</p>
				<div class="print-header-left">
					<div class="upper-left-header">
						<div class="print-logo">
							@if(!empty($company->logo))
							<img src="{{ FlySystem::getUrl(\Config::get('jp.BASE_PATH').$company->logo) }}" />
							@endif
						</div>
						<div class="header-company-details">
							<P class="header-company-name font-bold font-big">{{ $company->name }}</P>

							@if(($division = $invoice->division) && ($address = $division->address))
							<?php
							$secondLineAddress = $address->address_line_1 ? '<br>'.$address->address_line_1 . ', <br>' : '';
							?>
							<p class="header-company-address">
								{!! $address->address or '' !!},
								{!! $secondLineAddress !!}
								{{ $address->city }},
								{{ $address->state->code or '' }},
								{{ $address->zip }}
							</p>
							@else
							<?php
							$secondLineAddress = $company->office_address_line_1 ? '<br>'.$company->office_address_line_1 . ', <br>' : '';
							?>
							<p class="header-company-address">
								{!! $company->office_address or '' !!},
								{!! $secondLineAddress !!}
								{{ $company->office_city }},
								{{ $company->state->code or '' }},
								{{ $company->office_zip }}
							</p>
							@endif
							@if( $licenseNumbers = $company->present()->licenseNumber)
							<p class="license-text">License #: {{  $licenseNumbers }}</p>
							@endif
							<p class="phone-text">Phone #: {{ phoneNumberFormat($company->office_phone,$company->country->code) }}</p>
						</div>
					</div>
					<div class="lower-left-header">
						<p class="font-bold">{{ $customer->full_name or '' }}</p>
						@if(($customer->billing)
						&& ($customerAddress = $customer->billing->present()->fullAddress(null, true)))
						<p>{!! $customerAddress !!}</p>
						@else
						@if(($customer->address)
						&& ($customerAddress = $customer->address->present()->fullAddress(null, true)))
						<p>{!! $customerAddress !!}</p>
						@endif
						@endif
					</div>
				</div>
				<div class="print-header-right">
					<h4 class="font-bold">{{ $invoice->name }}</h4>
					<div class="invoice-details">
						<?php
						if(!isset($timezone)) {
							$timezone = \Settings::get('TIME_ZONE');
						}
						?>
						@if($invoice->date)
						<?php $dateTime = new Carbon\Carbon($invoice->date); ?>
						@else
						<?php $dateTime = convertTimezone($invoice->updated_at, $timezone); ?>
						@endif
						<p>
							<span class="detail-label">Invoice #:</span>
							<span class="detail-text">{{ $invoice->invoice_number }}</span>
						</p>
						<p>
							<span class="detail-label">Billed on:</span>
							<span class="detail-text">{{ $dateTime->format(config('jp.date_format')) }}</span>
						</p>
						@if($invoice->due_date)
						<?php $dueDate = new Carbon\Carbon($invoice->due_date); ?>
						<p>
							<span class="detail-label">Due Date:</span>
							<span class="detail-text">{{ $dueDate->format(config('jp.date_format')) }}</span>
						</p>
						@endif
						@if( ($settings = \Settings::get('JOB_INVOICE')) && ($settings['job_number']))
						<p>
							<span class="detail-label">Job ID:</span>
							<span class="detail-text">{{ $job->number }}</span>
						</p>
						@endif
						@if( ($settings = \Settings::get('JOB_INVOICE')) 
						&& ($settings['job_alt_id']) 
						&& ($job->alt_id) )
						<p>
							<span class="detail-label">Job #:</span>
							<span class="detail-text">{{ $job->full_alt_id }}</span>
						</p>
						@endif
						@if($invoice->unit_number)
						<p>
							<span class="detail-label">Unit #:</span>
							<span class="detail-text">{{ $invoice->unit_number }}</span>
						</p>
						@endif
						@if($job->insurance && ($job->insuranceDetails))
						<p>
							<span class="detail-label">Insurance Company:</span>
							<span class="detail-text">{{ $job->insuranceDetails->insurance_company or ''}}</span>
						</p>
						<p>
							<span class="detail-label">Claim #:</span>
							<span class="detail-text">{{ $job->insuranceDetails->insurance_number or ''}}</span>
						</p>
						@endif
					</div>
				</div>
			</div>
			<div class="print-header-wrap print-job-detail-row">
				<div class="print-header-left">
					<p>Contact Information:</p>
					{{ phoneNumberFormat($customer->present()->showCustomerPhone(), $customer->present()->showCustomerCountryCode()) }} <br/>{{ $customer->email }}
				</div>
				<div class="print-header-right">
					@if(($jobAddress = $job->address) && ($jobAddress = $jobAddress->present()->fullAddress(null, true)))
					<p>Job Address:</p>
					<p>{!! $jobAddress !!}</p>
					@endif
				</div>
			</div>
		</div>	
		<div class="jp-print-table-wrap">
			<div class="amount-wrap">
				<?php
				if ($invoice->open_balance <= 0) {
					$amountLabel = 'PAID';
					$totalAmountClass = "total-amt amt-paid";
				}else {
					$amountLabel = 'Amount Due';
					$totalAmountClass = "total-amt";
				}

				if ($invoice->open_balance > 0) {
					$amount = showAmount($invoice->open_balance);
				}else {
					$amount = showAmount($invoice->total_amount);
				}
				?>
				<div class="{{$totalAmountClass}}">{{ $amountLabel }}: {{ $amount }}</div>
				@if(!empty($payment_methods))
				<div class="amt-type"> Payment Method: 
					@foreach($payment_methods as $method)
					<span class="print-chips">{{ paymentMethod($method) }}</span>
					@endforeach
				</div>
				@endif
			</div>
			
			@if($branch = $invoice->branch)
			<div class="branch-detail">
				<b>SRS Branch:</b> {{ $branch->name }} ({{ $branch->branch_code }})
			</div>
			@endif

			<table class="table print-table">
				<tbody>
					<tr class="jp-table-head">
						<th width="55%" class="td-col-max">Activity</th>
						<th width="15%">Qty</th>
						<th width="15%">Rate</th>
						<th width="15%" class="text-right">Amount</th>
					</tr>
					<?php $noChargeAmt = 0; ?>
					@foreach($invoice->lines as  $line)
					<tr class="print-root-tier">
						<?php

						if(!$line->is_chargeable) {
							$noChargeAmt += $line->amount * $line->quantity;
						}

						$product = 'Services';
						if($line->trade && $line->workType){
							$product = $line->trade->name.'/'.$line->workType->name;
						}
						?>
						<td class="td-col-max">
							{{ $product}}
							<p class="text-prewrap-format">{{ $line->description}}</p>
						</td>
						<td>{{ $line->quantity }}</td>
						<td>{{ moneyFormat($line->amount) }}</td>
						<td class="text-right">{{ currencyFormat($line->amount * $line->quantity ) }}</td>
					</tr>
					@endforeach
				</tbody>
			</table>
			<div class="print-table-footer">
				@if($noChargeAmt)
				<div class="footer-value">
					<label>No Charge Amount</label>
					<span>- {{ showAmount($noChargeAmt) }}</span>
				</div>
				@endif
				@if($invoice->tax_rate && $invoice->taxable)
				<div class="footer-value">
					<label>Subtotal</label>
					<span>{{ showAmount($invoice->amount) }}</span>
				</div>

				<div class="footer-value">
					<label>Tax({{ $invoice->tax_rate }}%)</label>
					<span>{{ showAmount($invoice->total_amount - $invoice->amount)}}</span>
				</div>
				@endif
				<div class="footer-value">
					<label>Total</label>
					<span>{{ showAmount($invoice->total_amount) }}</span>
				</div>
				
				<div class="footer-value">
					<label>Amount Paid</label>
					<span> @if($amount_paid)- @endif {{ currencyFormat($amount_paid)}} </span>
				</div>

				<div class="footer-value">
					<label>Applied Credits</label>
					<span> @if($apply_credits)- @endif {{ currencyFormat($apply_credits)}} </span>
				</div>

				<div class="footer-value total">
					<label>Amount Due</label>
					<span>{{ showAmount($invoice->open_balance) }}</span>
				</div>
				@if($invoice->signature)
				<div class="pull-right signbox">
					<label>Customer Signature:</label>
					<div class="sign section-block">
						<div class="jp-border jp-signature" style="width:230px;height:90px">
							<div style="height: 70px;">
								<img src="{{ $invoice->signature }}" class="sign-temp" path="img/sign.png">
							</div>
							<div class="sign-date" style="margin-top: 0px; line-height: 20px; height: 20px;">{{\Carbon\Carbon::parse($invoice->signature_date, Settings::get('TIME_ZONE'))->format(config('jp.date_format'))}}</div>
						</div>
					</div>
				</div>
				@endif
				<div class="clearfix"></div>
			</div>
		</div>
		@if($invoice->note)
		<div class="print-note">
			<label>Note:</label>
			<p>{{ $invoice->note }}</p>
		</div>
		@endif
	</div>
</body></html>