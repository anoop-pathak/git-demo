<!DOCTYPE html>
<html class="no-js" ng-app="jobProgress" ng-controller="AppCtrl">

<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	<title> JobProgress </title>
	<link rel="stylesheet" href="{{ config('jp.site_url') }}app/styles/font-awesome.css">
	<!-- <link rel="stylesheet" href="{{ config('app.url') }}css/vendor.css">
		<link rel="stylesheet" href="{{ config('app.url') }}css/main.css" /> -->
		<meta name="viewport" content="width=device-width">
		<style type="text/css">
			body {
				background: #fff;
				margin: 0;
				font-size: 16px;
				font-family: Helvetica, Arial, sans-serif
			}
			p {
				margin: 0;
			}
			h1,
			h2,
			h3,
			h4,
			h5,
			h6 {
				margin: 0;
				margin-bottom: 5px;
			}
			.bd-t0 {
				border-top: 0 !important;
			}
			.container {
				background: #fff;
			}
			.page-content {
				padding: 40px 15px;
			}
			h2 {
				margin: 4px 0;
				font-size: 21px;
				font-weight: normal;
			}
			.logo img {
				width: 110px;
			}
			.header-part {
				width: 50%;
				float: left;
			}
			.header-part h2 {
				display: inline-block;
				vertical-align: middle;
			}
			.clearfix {
				clear: both;
			}
			.main-address {
				float: right;
				width: 50%;
			}
			.text-right {
				float: right;
			}
			.table {
				width: 100%;
				margin-top: 5px;
				margin-bottom: 0;
				border-collapse: collapse;
			}
			.logo img {
				height: auto;
				max-width: 100%;
				max-height: 100%;
				vertical-align: middle;
			}
			.table>tbody>tr>td,
			.table>tbody>tr>th,
			.table>tfoot>tr>td,
			.table>tfoot>tr>th,
			.table>thead>tr>td,
			.table>thead>tr>th {
				border-top: none;
				text-align: left;
			}
			.address {
				font-size: 13px;
				line-height: 1.5;
			}
			.address h3 {
				margin-bottom: 15px;
			}
			.job-number-row label {
				float: left;
				width: 100px;
			}
			.details-part {
				width: 30%;
				float: left;
			}
			.bill-details {
				width: 30%;
				float: left;
			}
			.job-cols {
				width: 18%;
				float: right;
				font-size: 13px;
			}
			.job-cols p {
				margin-bottom: 5px;
			}
			.content {
				margin-top: 40px;
				margin-bottom: 30px;
			}
			.main-heading {
				font-size: 22px;
				text-align: left;
				margin-bottom: 10px;
				font-weight: bold;
			}
/*.container {
	width: 800px;
	margin:auto;
	}*/
	.small-text {
		font-size: 13px;
	}
	.table tbody tr {
		border-bottom: 1px solid #eee;
		vertical-align: top;
	}
	.table thead th {
		border-top: 2px solid #333 !important;
		border-bottom: 2px solid #333;
	}
	thead th {
		padding: 12px;
	}
	tbody td {
		padding: 12px;
		font-size: 15px;
		line-height: 1.5;
	}
	.phone-text {
		margin-top: 4px;
	}
	.top-address {
		text-align: right;
	}
	.top-address h3{
		margin-bottom: 3px;
	}
	td h4 {
		font-weight: normal;
	}
	.mt20 {
		margin-top: 20px;
	}
	.note-section {
		margin-top: 40px;
		line-height: 1.6;
	}
</style>
</head>
<body>
	<div class="container">
		<div class="page-content">
			<div class="header">
				<div class="header-part">
					<h1 class="main-heading">Bill*</h1>
				</div>
				<div class="main-address">
					<div class="address top-address pull-right">
						<p>Billed To</p>
						<h3>{{ $company->name }}</h3>
						@if(($address = $company->address))
						<?php
						$secondLineAddress = $address->address_line_1 ? '<br>'.$address->address_line_1 . ', <br>' : '';
						?>
						<P>
							{!! $address->address or '' !!},
							{!! $secondLineAddress !!}
							{{ $address->city }},
							{{ $address->state->code or '' }},
							{{ $address->zip }}
						</P>
						@else
						<?php
						$secondLineAddress = $company->office_address_line_1 ? '<br>'.$company->office_address_line_1 . ', <br>' : '';
						?>
						<p>
							{!! $company->office_address or '' !!},
							{!! $secondLineAddress !!}
							{{ $company->office_city }},
							{{ $company->state->code or '' }},
							{{ $company->office_zip }}
						</p>
						@endif
						<p class="phone-text">phone {{ phoneNumberFormat($company->office_phone,$company->country->code) }}</p>
					</div>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="content">
				<div class="details-part">
					<div class="jobs-row">
						<div class="address customer-address">
							<h3>Billed By</h3>
						</div>
						<div class="address customer-address">
							{{ $vendor->display_name }}
							@if($vendorBillAddress = $vendorBill->address)
							<p>{!! $vendorBillAddress !!}</p>
							@endif
						</div>
					</div>
				</div>
				<div class="bill-details">
					<div class="jobs-row">
						<div class="address customer-address">
							<h3>Customer Details</h3>
						</div>
						@if($job->alt_id)
						<div class="address customer-address">
							Job #: <span>{{ $job->full_alt_id }}</span>
							@endif
							<p>
								@if(($customer->address)
								&& ($customerAddress = $customer->address->present()->fullAddress(null, true)))
								{!! $customerAddress !!}
								@endif
							</p>
						</div>
					</div>
				</div>
				<div class="job-cols mt20">
					<?php
					if(!isset($timezone)) {
						$timezone = \Settings::get('TIME_ZONE');
					}
					?>
					@if($vendorBill->bill_date)
					<?php $dateTime = new Carbon\Carbon($vendorBill->bill_date); ?>
					@else
					<?php $dateTime = convertTimezone($vendorBill->updated_at, $timezone); ?>
					@endif
					<p class="job-number-row">
						Billed On: <span>{{ $dateTime->format(config('jp.date_format')) }}</span>
					</p>
					@if($vendorBill->due_date)
					<?php $dueDate = new Carbon\Carbon($vendorBill->due_date); ?>
					<p class="job-number-row">
						Due On: <span>{{ $dueDate->format(config('jp.date_format')) }}</span>
					</p>
					@endif
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="clearfix"></div>
			<div class="table-responsive border-none">
				<table class="table">
					<thead>
						<tr>
							<th width="55%">Activity</th>
							<th width="15%">Qty</th>
							<th width="15%">Rate</th>
							<th width="15%">Amount</th>
						</tr>
					</thead>
					<tbody>
						@foreach($lines as  $line)
						<tr>
							<td>
								<h4>{{ $line->financialAccount->name }}</h4>
								<p class="small-text">{{ $line->description }}</p></td>
								<td>{{ $line->quantity }}</td>
								<td>{{ moneyFormat($line->rate) }}</td>
								<td>{{ currencyFormat($line->rate * $line->quantity) }}</td>
							</tr>
							@endforeach
							@if($vendorBill->tax_amount)
							<tr>
								<td></td>
								<td>Sub Total</td>
								<td></td>
								<td>{{ showAmount($vendorBill->total_amount) }}</td>
							</tr>
							@else
							<tr>
								<td></td>
								<td>Total</td>
								<td></td>
								<td>{{ showAmount($vendorBill->total_amount) }}</td>
							</tr>
							@endif
							@if($vendorBill->tax_amount)
							<tr>
								<td></td>
								<td>Tax</td>
								<td></td>
								<td>{{ showAmount($vendorBill->tax_amount)}}</td>
							</tr>
							<tr>
								<td></td>
								<td>Total Amount (including Tax)</td>
								<td></td>
								<td>{{ showAmount($vendorBill->total_amount + $vendorBill->tax_amount) }}</td>
							</tr>
							@endif
						</tbody>
					</table>
				</div>
				<div class="note-section mt20">
					@if($vendorBill->note)
					<h4>Note</h4>
					<p class="small-text">{{ $vendorBill->note }}</p>
				</div>
				@endif
			</div>
			<div class="clearfix"></div>
		</div>
	</body>
	</html