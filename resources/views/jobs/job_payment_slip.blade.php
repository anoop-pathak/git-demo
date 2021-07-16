<!DOCTYPE html>
<html class="no-js" ng-app="jobProgress" ng-controller="AppCtrl"><head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<title> JobProgress </title>
<link rel="stylesheet" href="{{ config('jp.site_url') }}app/styles/font-awesome.css">
<link rel="stylesheet" href="{{config('app.url')}}css/vendor.879fa015.css">
<link rel="stylesheet" href="{{config('app.url')}}css/main.css" />
<meta name="viewport" content="width=device-width">
<style type="text/css">
	body {
		background: #fff;
		margin: 0;
		font-size: 18px;
		font-family: Helvetica,Arial,sans-serif
	}
	p {
		margin: 0;
	}
	h1,h2,h3,h4,h5,h6 {
		margin: 0;
	}
	.bd-t0{
		border-top: 0 !important;
	}
	.container {
		/*width: 780px;*/
		/*margin: auto 50px;*/
		background: #fff;
	}
	.jobs-export {
		padding: 40px 15px;
	}
	h2 {
		margin: 4px 0;
		font-size: 21px;
		font-weight: normal;
	}
	.header-part {
		display: inline-block;
	}
	.header-part .job-col {
		padding: 0;
	}
	.header-part .date-format {
		font-size: 12px;
		margin: 0;
		margin-top: 3px;
	}
	.header-part h2 {
		display: inline-block;
		vertical-align: middle;
	}
	.clearfix {
		clear: both;
	}
	.main-logo {
		float: right;
	}
	.main-logo img {
		width: 200px;
	}
	.company-name {
		font-size: 18px;
		margin-bottom: 10px;
		margin-top: 5px;
	}
	.jobs-list {
		border: 1px solid #eee;
		margin: 15px 0 30px;
	}
	.jobs-row {
		font-size: 0;
		/*display: flex;
		-webkit-display: flex;*/
	}
	.jobs-row p {
		margin-bottom: 10px;
		font-size: 13px;
	}
	.job-col h3 {
		font-size: 14px;
		font-weight: normal;
		margin-bottom: 5px;
		white-space: nowrap;
		line-height: 20px;
	}
	.job-col .rep {
		margin-top: 10px;
	}
	.job-col {
		border-right: 1px solid #eee;
		display: inline-block;
		/*flex: 1 1 0;*/
		font-size: 13px;
		/*margin-top: 12px;*/
		padding: 0 18px;
		vertical-align: top;
		/*width: 45%;*/
		/*white-space: nowrap;*/
	}
	.job-col:last-child {
		border-color: transparent;
	}
	.upper-text {
		text-transform: uppercase;
	}
	.label {
		border-radius: 0.25em;
		color: #fff;
		display: inline;
		font-size: 75%;
		font-weight: 700;
		line-height: 1;
		padding: 0.2em 0.6em 0.3em;
		text-align: center;
		vertical-align: baseline;
		white-space: nowrap;
	}
	.separator {
		border: 1px solid #dfdfdf;
	}
	.pull-right {
		float: right;
	}
	.text-right {
		float: right;
	}
	.customer-name {
		margin-bottom: 8px;
	}
	.customer-name h3 {
		margin-bottom: 0;
	}
	.company-logo {
		width: 45px;
	    height: 45px;
	    border-radius: 50%;
	    border: 1px solid #ddd;
	    background: #fff;
	    text-align: center;
	    line-height: 40px;
	    display: inline-block;
	    padding: 4px;
	    vertical-align: middle;
	    overflow: hidden;
	    box-sizing: border-box;
	}
	.company-logo img {
		max-width: 100%;
		padding: 4px;
		box-sizing: border-box;
		transition: all 0.2s ease-in-out 0s;
		-webkit-transition: all 0.2s ease-in-out 0s;
	}
	.today-date {
		margin-top: 40px;
	}
	.today-date label {
		width: 170px;
		font-size: 16px;
	}
	.today-date p {
		/*text-align: right;*/
	}
    
    .invoice-heading {
    	color: #357ebd;
    	font-weight: normal;
    }
    .table {
    	width: 100%;
    	margin-top: 20px;
    	margin-bottom: 0;
    }
    .table tr td, .table tr th {
    	text-align: right;
    }
    .table tr th.text-left, .table tr td.text-left {
    	text-align: justify;
    }
    .balance-due {
    	/*border-top: 1px dashed #ccc;
    	padding: 15px 8px;*/
    	font-weight: bold;
    	font-size: 18px;
    }
    /*.balance-due span {
    	width: 50%;
    	text-align: right;
    	display: inline-block;
    	vertical-align: middle;
    	font-size: 18px;
    }*/
    /*.balance-due span:last-child {
    	font-size: 25px;
    }*/
    .billed-box {
    	border: 1px solid #ccc;
    	min-width: 250px;
    	text-align: center;
    	margin-top: 15px;
    }
    .billed-box .main-heading {
    	border-bottom: 1px solid #ccc;
    	text-align: left;
    }
    .billed-box .main-heading p {
    	display: inline-block;
    	vertical-align: middle;
    	padding: 6px 12px;
    	margin-bottom: 0;
    	/*background: #c81b1b;*/
    	font-weight: bold;
    	color: #fff;
    }
    .billed-box .main-heading span {
    	display: inline-block;
    	vertical-align: middle;
    	padding: 6px 0px;
    	padding-right: 10px;
    	margin-left: 5px;
    	font-weight: bold;
    }
    .billed-box h2 { 
    	margin: 0;
    	padding: 10px 0;
    }
    .bottom-table {
    	width: 100%;
    	float: right;
    	margin-top: 0;
    }
    .img-thumbnail {
    	margin-bottom: 22px;
    	height: 60px;
    }
    .small-font {
    	font-size: 13px;
    	font-weight: normal;
    }
    .logo {
    	height: 120px;
    	width: 120px;
    	border: 1px solid #ddd;
	    background: #fff;
	    text-align: center;
	    display: inline-block;
	    padding: 4px;
	    line-height: 110px;
	    vertical-align: middle;
	    overflow: hidden;
	    box-sizing: border-box;
	    margin-bottom: 10px;
	    border-radius: 8px;
    }
    .img-new-logo {
		max-height: 100%;
		padding: 4px;
		box-sizing: border-box;
		transition: all 0.2s ease-in-out 0s;
		-webkit-transition: all 0.2s ease-in-out 0s;
    }
    .table > tbody > tr > td, .table > tbody > tr > th, .table > tfoot > tr > td, .table > tfoot > tr > th, .table > thead > tr > td, .table > thead > tr > th {
    	border-top: none;
    }
    .table-striped > tbody > tr:nth-child(2n+1) > td, .table-striped > tbody > tr:nth-child(2n+1) > th {
	    background-color: #f5f5f5;
	}
	.address-label {
		display: block;
		font-weight: normal;
	}
	.address {
	    display: inline-block;
	    vertical-align: top;
	    /*margin-right: 30px;*/
	    margin-top: 10px;
	    width: 200px;
	    white-space: normal ! important;
	}
	.customer-address {
		margin-right: 10px;
	}
	.invoice-description {
		white-space: pre-line;
	 	text-align: justify;
	 	word-break: keep-all;
	}
	.job-number-row label {
		float: left;
	}
	.job-number-row span {
		display: block;
		margin-left: 100px;
	}
	.invoice-box-sec {
		max-width: 270px;
	}
	.main-head {
		text-align: right;
	}
	.job-number-row label {
		float: left;
	}
	.job-number-row span {
		display: block;
		margin-left: 170px;
	}
	.invoice-box-sec {
		max-width: 320px;
	}
	.main-head {
		text-align: right;
	}
	.insurance-section span {
		margin-left: 132px;
	}
	.insurance-section p {
		margin-bottom: 0;
	}
	.insurance-section {
		margin: 10px 0;
	}
	.sign {
    	display: inline-block;
    	vertical-align: middle;
    	position: relative;
    	text-align: center;
    	border: 1px solid #ccc;
    	margin-top: 1px;
    }
    .sign img {
    	max-height: 75%;
    	width: auto;
    	margin-top: 1px;
    }
    .sign .sign-date {
    	font-size: 10px;
    	height: 30px;
    	line-height: 18px;
    	border-top: 1px solid #ccc;
    	margin-top: 3px;
    }

	.balance-due-status {
		background: #298e29;
	}
	.payment-type {
		display: inline-block;
		margin-right: 3px;
		padding: 2px 5px;
		background: #eee;
		border-radius: 10px;
		font-size: 12px;
	}
</style>
</head>
<body>	
	<div class="container">
		<div class="jobs-export">
			<div class="header-part">
				<div class="logo">
				@if(!empty($company->logo))
					<img class="img-new-logo" src="{{ FlySystem::getUrl(config('jp.BASE_PATH').$company->logo) }}" />
				@endif
				</div>
				<p class="small-font">Payable to:</p>
				<h2>{{ $company->name }}</h2>
				<div class="clearfix"></div>

				<div class="jobs-row">
					<div class="job-col">
						<h3>{{ $company->office_address ?? '' }} {{ $company->office_city }}, 
							<br> {{ $company->state->code ?? '' }} 
							{{ $company->office_zip }}</h3>	
					</div>
				</div>
			</div>
			<div class="main-logo">
				<h1>Deposit / Receipt</h1>
				<div class="today-date invoice-box-sec">
					<p><label>Receipt #&nbsp;</label>{{ $jobPayment->serial_number }}	</p>
					<div class="clearfix"></div>
					@if($job->alt_id)
					<p class="job-number-row"><label>Job #&nbsp;</label><span>{{ $job->full_alt_id }}</span></p>	
					<div class="clearfix"></div>
					@endif

					<p class="job-number-row"><label>Job Id&nbsp;</label><span>{{ $job->number }}</span></p>
				
					@if($job->insurance)
						<div class="clearfix"></div>
						<p class="job-number-row"><label>Insurance Company&nbsp;</label><span>{{ $job->insuranceDetails->insurance_company ?? ''}}</span></p>	
						<div class="clearfix"></div>
						<p class="job-number-row"><label>Claim #&nbsp;</label><span>{{ $job->insuranceDetails->insurance_number ?? ''}}</span></p>
					@endif
				</div>
			</div>
			<div class="clearfix"></div>
			<!-- <h2 class="invoice-heading">INVOICE</h2> -->
			<div class="header-part">
				<!-- <h1>Sandbox Company_US_1</h1> -->
				<div class="jobs-row">
					<div class="job-col customer-name">
						<!-- <h3>123 Sierra Way <br>San Pablo, CA 87999</h3>	
						<h3>noreply@quickbooks.com</h3><br> -->
						<h3>Receipt For:</h3>
						<h2 style="display: block;">{{ $customer->full_name }}</h2>
						@if(($customer->billing) 
							&& ($customerAddress = $customer->billing->present()->fullAddress))
							<h3 class="address customer-address">
								<span class="address-label">Billing Address:</span>
									{!! $customerAddress !!}
							</h3>
						@else
							@if(($customer->address) 
							&& ($customerAddress = $customer->address->present()->fullAddress))
								<h3 class="address customer-address">
									<span class="address-label">Address:</span>
										{!! $customerAddress !!}
								</h3>
							@endif
						@endif
						
						<?php $job = $job; ?>
						 @if(($job->address) && ($jobAddress = $job->address->present()->fullAddress) )
							<h3 class="address">
								<span class="address-label">Job Address:</span>
								{!! $jobAddress !!}
							</h3>
						@endif
					</div>
				</div>
			</div>

			<div class="main-logo" style="padding-top: 38px;width: 317px;">
				
				<!-- show payment method -->

				@if($jobPayment->method)
				<div class="payment-method">
					<p class="small-font">Payment Method:</p>
					<span class="payment-type">{{ paymentMethod($jobPayment->method) }}</span>
				</div>
				@endif
				<div class="billed-box">
					<div class="main-heading">
						<p class="balance-due-status">Received On</p>
						<span>on {{ Carbon\Carbon::parse($jobPayment->date)->format(config('jp.date_format')) }}</span>
					</div>
						<h2>
						{{currencyFormat($jobPayment->payment)}}
					</h2>
				</div>
			</div><br>
			<div class="clearfix"></div>

			<table class="table table-striped table-bordered">
				<tr>
					<th class="text-left" style="width: 475px;">Activity</th>
					<th style="text-align: center; width: 120px;">Qty</th>
					<th style="text-align: center;">Rate</th>
					<th style="text-align: center;">Amount</th>
				</tr>
				<?php $total =0; ?>
				@forelse($jobPayment->details as $detail)
				<tr>
					<td class="text-left invoice-description">{{ $detail->description}}</td>
					<td style="text-align: center;">{{ $detail->quantity }}</td>
					<td style="text-align: center;">{{ moneyFormat($detail->amount) }}</td>
					<?php 
						$lineTotal =  $detail->getTotalAmount();
						$total += $lineTotal;
					?>
					<td style="text-align: center;">{{ moneyFormat($lineTotal) }}</td>
				</tr>
				@empty
				<tr><?php 
						$trades   = $job->trades->pluck('name')->toArray();
						if(in_array( 'OTHER', $trades) && ($job->other_trade_type_description)) {
							$otherKey = array_search('OTHER', $trades);
							unset($trades[$otherKey]);
							$other  = 'OTHER - ' . $job->other_trade_type_description;
							array_push($trades, $other);
						}
						$description = implode(', ', $trades);?>
					<td style="text-align: left;" class="invoice-description">{{$job->number}} / {{$description}}</td>
					<td style="text-align: center;">1</td>
					<td style="text-align: center;">{{ $jobPayment->payment }}</td>
					<td style="text-align: center;">{{ $total = $jobPayment->payment }}</td>
				</tr>
				@endforelse
			</table>
			<table class="table table-striped bottom-table">
				<tr></tr>
				<tr>
					<td style="width: 475px;"></td>
					<td style="width: 121px;">&nbsp;</td>
					<td style="border: 1px solid #ddd; border-right: 0;">Total</td>
					<td style="border: 1px solid #ddd; border-left: 0;">{{ currencyFormat($total) }}</td>
				</tr>
			</table>
		</div>
	</div>
	
</body></html>