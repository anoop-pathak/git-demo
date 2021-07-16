<!DOCTYPE html>
<html class="no-js" ng-app="jobProgress" ng-controller="AppCtrl"><head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<title ng-bind="pageTitle"> JobProgress </title>
 <link rel="stylesheet" href="{{ config('jp.site_url') }}app/styles/font-awesome.css">
 <link rel="stylesheet" href="{{config('app.url')}}css/vendor.879fa015.css">
 <link rel="stylesheet" href="{{config('app.url')}}css/main.css" />
<meta name="viewport" content="width=device-width">
<style type="text/css">
	body {
		background: #fff;
		margin: 0;
		font-size: 14px;
		font-family: Helvetica,Arial,sans-serif;
		width: 794px;
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
		/*width: 780px; */
		/*margin: auto 50px;*/
		background: #fff;
	}
	.jobs-export {
		padding: 0 20px;
	}
	h2 {
		margin: 4px 0;
		font-size: 21px;
		font-weight: normal;
	}
	.header-part {
		display: inline-block;
	}
	.main-logo h1 {
		font-size: 22px;
		text-align: right;
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
		font-size: 13px;
		padding: 0 18px;
		vertical-align: top;
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
		display: inline-block;
		padding-right: 15px;
		vertical-align: middle;
		width: 45px;
	}
	.company-logo img {
		border-radius: 50%;
		height: 45px;
		width: auto;
		background-color: #fff;
		border: 1px solid #ddd;
		display: inline-block;
		line-height: 1.42857;
		max-width: 100%;
		padding: 4px;
		transition: all 0.2s ease-in-out 0s;
		-webkit-transition: all 0.2s ease-in-out 0s;
	}
	.today-date {
		margin-top: 40px;
	}
	.today-date label {
		width: 100px;
		font-size: 14px;
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
    	text-align: left;
    }
    .balance-due {
    	font-weight: bold;
    	font-size: 18px;
    }
	.table tr {
		page-break-inside: avoid;
	}
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
    	background: #c81b1b;
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
    	width: 50%;
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
	    border: 1px solid rgb(204, 204, 204);
	    text-align: center;
	    height: 130px;
	    line-height: 128px;
	    width: 128px;
	    border-radius: 8px;
	    box-sizing: border-box;
	    margin-bottom: 10px;
    }
    .logo img {
	    height: auto;
	    max-width: 100%;
	    max-height: 100%;
	    vertical-align: middle;
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
	    margin-top: 10px;
	    width: 200px;
	    white-space: normal ! important;
	}
	.customer-address {
		margin-right: 10px;
	}
</style>
</head>
<body style="width: 794px;">
	<div class="container">
		<div class="jobs-export">
			<div class="header-part">
				<div class="logo">
				@if(! empty($company->logo) )
					<img class="img-new-logo" src="{{ FlySystem::getUrl(config('jp.BASE_PATH').$company->logo) }}" />
				@endif
				</div>
				<p class="small-font">Paid By:</p>
				<h2>{{ $company->name }}</h2>
				<div class="clearfix"></div>

				<div class="jobs-row">
					<div class="job-col customer-name">
						<h3>{{ $company->office_address ?? '' }} {{ $company->office_city }},
							<br>{{ $company->state->code ?? '' }} 
							{{ $company->office_zip }}</h3>	
		
					</div>
				</div>
			</div>
			<div class="main-logo">
				<h1>Credit Memo</h1>
				<div class="today-date">
					<?php
						$currentDate = \Carbon\Carbon::parse($jobCredit->date);
					?>
					<p><label>Date:&nbsp;</label>{{ $currentDate->format(config('jp.date_format')) }}</p>
					<p><label>CREDIT #&nbsp;</label>{{ $jobCredit->id }}	</p>
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
						<h3>Credit To:</h3>
						<h2 style="display: block;">{{ $customer->first_name ?? '' }} {{ $customer->last_name }}</h2>

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

						<?php $job = $jobCredit->job; ?>
						 @if(($job->address) && ($jobAddress = $job->address->present()->fullAddress) )
							<h3 class="address">
								<span class="address-label">Job Address:</span>
								{!! $jobAddress !!}
							</h3>
						@endif


					</div>
				</div>
			</div>

			<div class="main-logo">
			</div><br>
			<div class="clearfix"></div>

			<table class="table table-striped table-bordered" width="100%">
				<tr>
					<th class="text-left" width="50%">Activity</th>
					<th width="50%">Amount</th>
				</tr>
				<tr>
					<td class="text-left"><strong>Services</strong><br> {{ $description ?? '' }} </td>

					<td>{{ currencyFormat($jobCredit->amount) }}</td>
				</tr>
			</table>
			<table class="table table-striped bottom-table">
				<tr></tr>
				<tr class="balance-due">
					<td>Total Credit</td>
					<td>${{ currencyFormat($jobCredit->amount) }}</td>
				</tr>
			</table>
		</div>
	</div>
</body></html>