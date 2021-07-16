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
	<div class="print-main-wrapper">
		<div class="print-header-outer">
			<div class="print-header-wrap">
				<div class="print-header-left">
					<div class="upper-left-header">
						<div class="print-logo">
							@if(!empty($company->logo))
							<img src="{{ FlySystem::getUrl(\Config::get('jp.BASE_PATH').$company->logo) }}" />
							@endif
						</div>
						<div class="header-company-details">
							<P class="header-company-name font-bold font-big">{{ $company->name }}</P>

							@if(($division = $job->division) && ($address = $division->address))
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
					<h4 class="font-bold">INVOICE</h4>
					<div class="invoice-details">
						<?php
						if(!isset($timezone)) {
							$timezone = \Settings::get('TIME_ZONE');
						}
						?>
						@if($job->date)
						<?php $dateTime = new Carbon\Carbon($job->date); ?>
						@else
						<?php $dateTime = convertTimezone($job->updated_at, $timezone); ?>
						@endif
						<p>
							<span class="detail-label">Invoice #:</span>
							<span class="detail-text">Cumulative</span>
						</p>
						@if( ($settings = Settings::get('JOB_INVOICE'))
						&& ($settings['job_alt_id'])
						&& ($job->alt_id) )
						<?php
							if($job->isProject()) {
								$altIdLabel = 'Project #';
							} else {
								$altIdLabel = 'Job #';
							}
						?>
						<p>
							<span class="detail-label">{{ $altIdLabel }}</span>
							<span class="detail-text">{{ $job->full_alt_id }}</span>
						</p>
						@endif
						@if(($settings = Settings::get('JOB_INVOICE')) && ($settings['job_number']))
						<?php 
							if($job->isProject()) {
								$jobNumber = 'Project ID';
							} else {
								$jobNumber = 'Job ID';
							}
						?>
						<p>
							<span class="detail-label">{{ $jobNumber }}</span>
							<span class="detail-text">{{ $job->number }}</span>
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
				if(!isset($timezone)) {
					$timezone = \Settings::get('TIME_ZONE');
				}
				$dueBalance = numberFormat((($financial_calculation->job_invoice_amount + $financial_calculation->job_invoice_tax_amount + $financial_calculation->total_change_order_amount) - $total_applied_payment) - $total_applied_credit); ?>
				<?php
				if ($dueBalance <= 0) {
					$amountLabel = 'PAID';
					$totalAmountClass = "total-amt amt-paid";
				}else {
					$amountLabel = 'Balance Due';
					$totalAmountClass = "total-amt";
				}
				if ($dueBalance == 0) {
					$amount = showAmount($financial_calculation->total_amount);
				}else {
					$amount = showAmount($dueBalance);
				}
				?>
				<div class="{{$totalAmountClass}}">{{ $amountLabel }}: {{ $amount }}</div>
			</div>
			<div class="clearfix"></div>
			<?php 
			$subTotal = 0;
			$totalTax = 0;
			$lineTotalAmount = 0;
			$totalAmount = 0;
			$taxAmount = 0;
			$totalQtyAmount = 0;
			?>
			<table class="table print-table">
				<tbody>
					<tr class="jp-table-head">
						<th width="55%" class="td-col-max">Activity</th>
						<th width="15%">Amount</th>
						<th width="15%">Tax</th>
						<th width="15%" class="text-right">Line Total</th>
					</tr>
					@foreach($invoices as $invoice)
					<tr>
						<?php $invoiceTitle = $invoice->title; ?>
						@foreach($invoice->lines as  $line)
						<?php $invoiceDesc = $line->description; ?>
						@if($invoice->name != JobInvoice::DEFAULT_INVOICE_NAME)
						<?php $invoiceTitle = $invoice->name; ?>
						@endif
						<td class="td-col-max">
							{{ $invoiceTitle}}
							<p class="text-prewrap-format">{{" $invoiceDesc". " (Reference # {$invoice->invoice_number})"}}</p>
						</td>
						<?php
						$totalQtyAmount = $line->amount * $line->quantity;
						$subTotal += $totalQtyAmount;
						$taxAmount = $invoice->tax_rate;
						if($invoice->tax_rate){
							$taxAmount = calculateTax($totalQtyAmount, $invoice->tax_rate);
						}
						$lineTotalAmount += $totalQtyAmount + $taxAmount;
						$totalAmount = $totalQtyAmount + $taxAmount;
						?>
						<td>{{ moneyFormat($totalQtyAmount) }}</td>
						<td>
							@if($invoice->tax_rate)
							{{ moneyFormat($taxAmount) }}
							@else
							--
							@endif
						</td>
						<td class="text-right">{{ moneyFormat($totalAmount) }}</td>
					</tr>
					@endforeach
					@endforeach
					@foreach($changeOrders as $changeOrder)
					<?php
					$title = "Change Order #".$changeOrder->order;
					$changeOrderEntities = $changeOrder->entities;
					$invoice = $changeOrder->invoice;
					if($invoice) {
						$title .= " (Reference # {$invoice->invoice_number})";
					}
					?>
					@foreach($changeOrderEntities as $line)
					<tr>
						<td class="td-col-max">
							{{ $title}}
							<p class="text-prewrap-format">{{ $line->description}}</p>
						</td>
						<?php
						$totalQtyAmount = numberFormat($line->amount * $line->quantity);
						$lineAmount = $totalQtyAmount;
						if($changeOrder->taxable) {
							$lineAmount = numberFormat(totalAmount($totalQtyAmount, $changeOrder->tax_rate));
							$taxAmount = $lineAmount - $totalQtyAmount;
						}
						$lineTotalAmount += $lineAmount;
						$subTotal += $totalQtyAmount;
						?>
						<td>{{ moneyFormat($totalQtyAmount)}}</td>
						<td>
							@if($changeOrder->taxable)
							{{ moneyFormat($taxAmount) }}
							@else
							--
							@endif
						</td>
						<td class="text-right">{{ moneyFormat($lineAmount) }}</td>
					</tr>
					@endForeach
					@endForeach
					@foreach($total_payments as $payment)
					<tr>
						<?php
						$invoice = $payment->jobInvoice;
						if($invoice) {
							$invoiceRef = "Invoice Ref # {$invoice->invoice_number}";
						}
						if($payment->ref_id) {
							$jobPayment = $payment->refJobPayment;
						}else{
							$jobPayment = $payment->jobPayment;
						}
						$paymentTitle = "Payment<br> Reference # {$jobPayment->serial_number}, {$invoiceRef}";
						?>
						<td>{{ $paymentTitle }}</td>
						<td>{{ moneyFormat($payment->amount) }}</td>
						<td>--</td>
						<td class="text-right">-{{ moneyFormat($payment->amount) }}</td>
					</tr>
					@endforeach
					@foreach($total_credit_payments as $creditPayment)
					<tr>
						<?php
						$invoice = $creditPayment->jobInvoice;
						if($invoice) {
							$invoiceRef = "Invoice Ref # {$invoice->invoice_number}";
						}
						$paymentTitle = "Credit<br> Reference # {$creditPayment->credit_id}., {$invoiceRef}";
						?>
						<td>{{ $paymentTitle }}<br>
							{{ $creditPayment->jobCredit->note }}</td>
						<td>{{ moneyFormat($creditPayment->amount) }}</td>
						<td>--</td>
						<td class="text-right">-{{ moneyFormat($creditPayment->amount) }}</td>
					</tr>
					@endforeach
					<tr>
						<td class="font-bold">Total</td>
						<td>{{showAmount($subTotal)}}</td>
						<td>{{ showAmount( $lineTotalAmount - $subTotal)}}</td>
						<td class="text-right">{{ showAmount($lineTotalAmount) }}</td>
					</tr>
				</tbody>
			</table>
			<div class="print-table-footer">
				<div class="footer-value">
					<label>Payment Received</label>
					<span>{{ showAmount(-$total_applied_payment)}}</span>
				</div>
				<div class="footer-value">
					<label>Applied Credits</label>
					<span> {{ showAmount(-$total_applied_credit)}} </span>
				</div>
				<div class="footer-value total">
					<label>Amount Due</label>
					<span> {{showAmount($dueBalance)}} </span>
				</div>
			</div>
			@if($job->cumulativeInvoiceNote)
			<div class="print-note">
				<label>Note:</label>
				<p>{{ $job->cumulativeInvoiceNote->note }}</p>
			</div>
			@endif
		</div>
	</div>
</body>
</html>