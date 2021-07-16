<!DOCTYPE html>
<html class="no-js"><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

<title> JobProgress </title>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
<link rel="stylesheet" href="{{config('app.url')}}css/vendor.879fa015.css">
<link rel="stylesheet" href="{{config('app.url')}}css/main.css" />
<meta name="viewport" content="width=device-width">
<style type="text/css">
	body {
		background: #fff;
		margin: 0;
		font-family: Helvetica,Arial,sans-serif;
		font-size: 18px;
		color: #333;
	}
	p {
		margin: 0;
	}
	h1,h2,h3,h4,h5,h6 {
		margin: 0;
	}
	.container {
		text-align: left;
		width: 100%;
		margin: auto;
		background: #fff;
	}
	.jobs-export {
		padding: 15px;
	}
	h1 {
		margin: 4px 0;
		font-size: 26px;
		font-weight: normal;
	}
	.header-part {
		display: inline-block;
		width: 70%;
	}
	.header-part .date-format {
		font-size: 13px;
		margin: 0;
		margin-top: 3px;
	}
	.header-part h1 {
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
		opacity: 0.6; 
	}
	.company-name {
		font-size: 20px;
		margin-bottom: 10px;
		margin-top: 5px;
	}
	.filters-section h5 {
		font-weight: bold;
		font-size: 18px;
		margin-bottom: 5px;
	}
	.filters-section label {
		color: #333;
		font-size: 18px;
		font-weight: normal;
	}
	.filters-section label span {
		color: #000;
	}
	.filters-section label span.trade {
		text-transform: uppercase;
	}
	.upper-text {
		text-transform: uppercase;
	}
	.desc {
		margin-right: 15px;
		padding-right: 15px;
	}
	.customer-desc{
		padding-bottom: 10px;
		text-align: justify;
	}
	.customer-desc b { 
		margin-bottom: 10px;
		font-size: 18px;
	}
	.second-part{
		border-top: 1px solid #ccc;
		padding-top: 15px;
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
	.label-default {
		background-color: #777;
	}
	.table {
		border-collapse: collapse;
		width: 100%;
		border: 1px solid #ddd;
		margin: 20px 0;
		table-layout: fixed;
	}
	.table tr.page-break {
		border-bottom: 1px solid #ccc;
	}
	.table tr.page-break table {
		border-collapse: collapse;
		table-layout: fixed;
	}
	.page-break {
		page-break-inside: avoid;
	}
	.table th {
		border-bottom: 1px solid #ccc;
	}
	.table th span {
		font-weight: bold;
	}
	.table tr.page-break:nth-child(2n+1) {
		background-color: #f9f9f9;
	}
	.table th, .table tr.page-break td td {
		padding: 10px 3px;
	}
	.stage i {
		font-style: normal;
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
	.reps{
		width: 150px;
	}
	.trades{
		width: 230px;	
	}
	.legend > span{
		float: right;
		font-size: 18px;
	}
	.customer-rep-heading{
		white-space: nowrap;
	}
	.flag{
		display: block;
		margin-top: 5px;
	}
	.customer-flags{
		margin-bottom: 10px;
	}
	.job-flags{
		margin-bottom: 5px;
		margin-top: 0;
	}
	.job-flags span{
		/*margin-bottom: 5px;
		display: inline-block;*/
	}
	.upcoming-appointment-title {
		display: inline-block;
		margin-bottom: 5px;
		width: 82%;
	}
	.activity-img{
		width: auto; 
		min-width: 24px; 
		top: 50%;
		height: 24px;
		float: left;
		border-radius: 50%;
		margin-right: 5px;
		background: #ccc;
		text-align: center;
	}
	.appointment-sr-container{
		padding: 10px 0px; 
		float: left; 
	}
	.appointment-sr-container .activity-img p{
		padding: 4px 0;
		font-size: 18px;
		color: #333;
	}
	.job-detail-appointment {
		border-bottom: 1px solid #ccc;
		cursor: pointer;
		padding: 10px 0;
		font-size: 18px;
		margin-left: 40px;
	}
	.job-detail-appointment:last-child {
		border-bottom: none;
	}
	.text-right{
		float: right;
	}
	.today-date p{
		text-align: right;
		padding-bottom: 3px;
		/*font-weight: bold;*/
	}
	.today-date p label {
		color: #333;
	}
	.location-box{
		display: inline-block;
	}
	.appointment-desc label, .location-box label {
		/*font-weight: normal;*/
		color: #333;
	}
	.appointment-container{
		margin-left: 0px;
		padding-left: 0px;
	}
	.appointment-meta{
		margin-bottom: 5px;
	}
	.btn-flags {
		border: none;
		color: #fff;
		background: #777;
		font-size: 14px;
		cursor: default;
		height: 17px;
		width: auto;
		line-height: 17px;
		margin-right: 15px;
		position: relative;
		padding-left: 8px;
		padding-right: 8px;
		border-radius: 3px;
		display: inline-block;
		vertical-align: middle;
		margin-bottom: 5px;
	}
	.btn-flags:hover {
		color: #fff;
	}
  	/*.btn-flags:after {
    	position: absolute;
      	top: 0px;
      	right: -21px;
      	content: "";
      	border-color: transparent transparent transparent #777;
      	border-style: solid;
      	border-width: 11px;
      	height: 0;
      	width: 0;
      }*/
      td {
      	vertical-align: top;
      }
      .description {  
      	text-align: justify;
      	white-space: pre-wrap;
      }
/*    .assign_to {
    	font-weight: bold;
    }*/
    .child-page {    
    	background: rgba(240, 173, 78, 0.1);   
    }
    .btn-flags.label-warning { background: #f0ad4e;   }
    .jobs-multiproject-btn {
    	margin-top: 3px;
    }
    .jobs-multiproject-btn .btn-flags{
    	font-size: 11px;
    	padding: 1px 6px;

    }

    p, span {
    	font-weight: normal;
    }
    label {
    	color: inherit;
    }
    .job-heading {
    	/*color: #434343;*/
    }
    .appointment-container p {
    	font-size: 18px;
    }
    .no-record {
    	margin: 50px auto;
    	text-align: center;
    	font-size: 30px;
    	padding: 50px;
    }
    .appointment-meta .assign_to {
    	margin: 3px 0;
    }	
    .appointment-label {
    	float: left; 
    	width: 90px;
    }
    .appointment-span-desc {
    	display: block; 
    	margin-left: 90px;
    }
    /*to avoid repeating header*/
    thead, tfoot { display: table-row-group }
    .cost-information > div {
    	line-height: 26px;
    }
    .job-container .finance-table tr td {
    	padding: 10px;
    	font-weight: normal;
    }
    .customer-info-print p {
    	font-size: 16px;
    }
    .customer-ref span {
    	font-size: 18px;
    }
    .cost-information>div span:last-child {
    	float: right;
    }
    .preview-finance-tabel-container .preview-finance-tabel {
    	width: 45%;
    }
    .help-text-selling-price {
    	text-transform: none;
    }
    .job-container .logo-name {
    	margin-top: 0;
    }
    .jp-panel .jp-panel-heading {
    	margin-bottom: 10px;
    }
    .job-container h2 {
    	margin-top: 5px;
    }
    .financial-loss {
    	color: red;
    }
    .financial-profit {
    	color: green;
    }

</style>
</head>
<body>
	<?php $costType = 'Projected'; ?>
	@if($worksheet->enable_actual_cost)
	<?php $costType = 'Actual'; ?>
	@endif
	<div class="container">
		<div class="jobs-export job-container jp-panel jp-panel-primary">
			<div class="jp-panel-heading">
				<h2>
					<div>
						Profit loss worksheet
						<div class="customer-info-print" style="padding-bottom:0;">
							<p class="ng-binding">{{ $job->customer->first_name ?? ''}} {{ $job->customer->last_name ?? ''}} / {{ $job->number ?? ''}}</p>
							<p>
								<span>
									{{ implode(', ',$job->trades->pluck('name')->toArray()) }}
								</span>
							</p>
						</div>
					</div>
					<div class="pl-total" style="font-size:16px;padding-bottom:5px;padding-top:0;">
						<span>
							Profit / Loss <span class="help-text-selling-price">({{ $costType }}): </span>
							<?php 
							$actualAmount = $financial_calculation->total_amount - ($worksheet->total + $financial_calculation->total_commission) ;
							 ?>
							 @if($actualAmount >= 0)
							<span class="financial-profit">
							{{ currencyFormat($actualAmount) }}
							</span>
							@else
							<span class="financial-loss">
							-{{ currencyFormat(abs($actualAmount)) }}
							 </span>
							@endif

						</div>
						<div class="clearfix"></div>
					</h2>
				</div>
				<div class="col-xs-12" style="padding-top: 15px;">
					<div class="row">
						<div class="col-xs-3">
							@if(!empty($company->logo))
							<div class="compant-logo">
								<div class="logo-name">
									<div class="profile-pic">
										<img alt="{{ $company->name }}" class="img-thumbnail" src="{{ FlySystem::getUrl(config('jp.BASE_PATH').$company->logo) }}">
									</div>
								</div>
							</div>
							@endif
							<div class="customer-ref" style="display: block;margin-top:10px;">
								<h2 class="inlineblock margin" style="padding-bottom: 0">
									<span class="margin">Company Name:  </span>
									<span class="ng-binding">{{ $company->name }}</span>
								</h2>
							</div>
							<div class="customer-ref">
								<h2 class="padding0">
									<span>Customer Rep: </span>
									<span style="text-transform: Capitalize;;text-transform: Capitalize;;text-transform: Capitalize;;text-transform: Capitalize;" ng-bind="name" class="word-break ng-binding ng-isolate-scope" type="rep" last-name="customer.rep.last_name" first-name="customer.rep.first_name">
										@if(isset($customer->rep->first_name) || isset($customer->rep->last_name))
										{{ $customer->rep->first_name ?? '' }} {{ $customer->rep->last_name ?? '' }}
										@else
										Unassigned
										@endif
									</span>
								</h2>
							</div>
						</div>

						<div class="col-xs-9">
							<div class="row">
								<div class="col-sm-6 col-xs-offset-1 col-xs-6 cost-information">
									<div>
										<span>Job Price:</span>
										<span class="ng-binding">{{ currencyFormat($job->amount) }}</span>
									</div>
									<div>
										<span>Change Order:</span>
										<span>{{ currencyFormat($financial_calculation->total_change_order_amount) }} </span>
									</div>
									<div>
										<span>Payment Received:</span>
										<span>
											{{ currencyFormat($financial_calculation->total_received_payemnt) }}
										</span>
									</div>
									<div>
										<span>Credit(s):</span>
										<span>
											{{ currencyFormat($financial_calculation->total_credits) }}
										</span>
									</div>
									<div>
										<span>Amount Owed:</span>
										<span class="ng-binding">
											@if($financial_calculation->pending_payment >= 0)
											{{ currencyFormat($financial_calculation->pending_payment )}}
											@else
											-{{ currencyFormat(abs($financial_calculation->pending_payment))}}
											@endif
										</span>
									</div>
								</div>
								<div class="col-sm-5 col-xs-5 cost-information" style="border-left: 1px solid #ccc;min-height: 85px;">
									<div>
										<span>Cost</span>
										<span>
											{{currencyFormat($worksheet->total)}}
										</span>
									</div>

									@foreach($categories as $category)
									<div>
										<span>{{ $category->name }}</span>
										<span>
											{{ currencyFormat($category->total_cost) }}
										</span>
									</div>                                	
									@endforeach
									<div>
										<span>Commissions</span>
										<span>
											{{ currencyFormat($financial_calculation->total_commission) }}
										</span>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="clearfix"></div><br>
				<div id="financial-section-job">
					<div class=" ng-scope" ng-if="previewMode">
					<div ng-include="'views/includes/financial/sheet/section-preview.html'" class="ng-scope"><div class="">
							<table class="table table-striped table-full-width margin">
								<thead>
									<tr>
										<th style="width: 3%"><span>#</span></th>
										<th style="width: 13%"><span>Type</span></th>
										<th style="width: 10%"><span>Name</span></th>
										<th style="width: 31%"><span>Description</span></th>
										<th style="width: 10%"><span>Unit</span></th>
										<th style="width: 10%"><span>
										<?php $costLabel = ''; ?>
										@if($worksheet->enable_actual_cost)
										 <?php $costLabel = 'Actual'; ?>
										@endif {{$costLabel}} Cost/Qty</span></th>
										<th style="width: 11%"><span>{{ $costLabel }} Qty</span></th>
										<th style="width: 12%"><span>Cost</span></th>
									</tr>
								</thead>
								<tbody>
									@foreach($financial_details as $key => $financialDetail)
									<?php 
									$quantity = $financialDetail->quantity;
									$unitCost = $financialDetail->unit_cost;
									if($worksheet->enable_actual_cost) {
										$quantity = $financialDetail->actual_quantity;
										$unitCost = $financialDetail->actual_unit_cost;	
									}
									?>
									<tr>
										<td>{{ ++$key }}</td>
										<td>{{ $financialDetail->category->name }}</td>
										<td>{{ $financialDetail->product_name }}</td>
										<td>{{ $financialDetail->description }}</td>

										<td>{{ $financialDetail->unit }}</td>
										<td>{{ currencyFormat($unitCost) }}</td>
										<td> {{ $quantity }}</td>
										<td>{{ currencyFormat($unitCost * $quantity) }}</td>
									</tr>
									@endforeach
									<tr class="finance-table-footer">
										<td colspan="5"> </td>
										<td> Total: </td>
										<td>&nbsp;</td>
										<td class="ng-binding">{{ currencyFormat($worksheet->total) }}</td>
									</tr>
								</tbody></table>
							</div></div>
						</div><br>
						<div class="table-responsive profit-loss-footer preview-finance-tabel-container ng-scope">
							<table style="float:left;" class="table table-striped table-hover table-full-width margin preview-finance-tabel">
								<tbody>
									<tr>
										<td> Selling Price</td>
										<td></td>
										<td class="ng-binding">{{ currencyFormat($job->amount) }}</td>
									</tr>
									<tr>
										<td>Change Order</td>
										<td></td>
										<td class="ng-binding">{{ currencyFormat( $financial_calculation->total_change_order_amount) }}</td>
									</tr>
									<tr>
										<td>Total Job Price</td>
										<td></td>
										<td class="ng-binding">{{ currencyFormat( $financial_calculation->total_amount) }}</td>
									</tr>
								</tbody></table>
								<table class="table table-striped table-hover table-full-width margin preview-finance-tabel">
									<tbody><tr>
										<td> Cost to the job <span class="help-text-selling-price">({{ $costType }})</span> </td>
										<td>&nbsp;</td>
										<td class="ng-binding" style="text-align: right">{{ currencyFormat($worksheet->total) }}</td>
									</tr>
									<tr>
										<td>Commissions</td>
										<td></td>
										<td style="text-align: right">{{ currencyFormat( $financial_calculation->total_commission) }}
										</td>
									</tr>
									@if(!$worksheet->enable_actual_cost)
									<tr>
										<td>Total Cost</td>
										<td></td>
										<td style="text-align: right">
											{{ currencyFormat($worksheet->total + $financial_calculation->total_commission) }}
										</td>
									</tr>
									@endif
									<tr>
										<td>Profit / Loss <span class="help-text-selling-price">({{ $costType }})</td>
										<td></td>
										<td style="text-align: right">
												<?php
												$amount = $financial_calculation->total_amount - ($worksheet->total + $financial_calculation->total_commission) ;
												 ?>
												@if($amount >= 0)
												<span class="financial-profit">
												{{ currencyFormat($amount) }}
												</span>
												@else
												<span class="financial-loss">
												-{{ currencyFormat(abs($amount)) }}
												</span>
												@endif
											</span>
										</td>
									</tr>
								</tbody></table>
						</div>
						</div>
						</div>
					</div>
				</div>
			</body>
			</html>