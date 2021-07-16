<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">

	<title>Customer Web Page</title>

	<script src="{{config('app.url')}}js/jquery-1.12.3.min.js"></script>
	<link rel="stylesheet" href="{{config('app.url')}}css/font-awesome.min.css">
	<link rel="stylesheet" type="text/css" href="{{config('app.url')}}css/main.css">
	<link rel="stylesheet" type="text/css" href="{{config('app.url')}}css/bootstrap.css">
	<link rel="stylesheet" type="text/css" href="{{config('app.url')}}css/fullCalendar.css">
	<link rel="stylesheet" type="text/css" href="{{config('app.url')}}customer_job_preview/css/jquery.bxslider/jquery.bxslider.css">
	<link rel="stylesheet" type="text/css" href="{{config('app.url')}}customer_job_preview/css/bootstrap-datepicker.min.css">
	<link rel="stylesheet" type="text/css" href="{{config('app.url')}}customer_job_preview/css/public-customer.css">
	<style>
		.qb-icon-white {
		    background: url('{{config('app.url')}}qb-pay/img/qb-icon-white.png');
	        background-size: cover;
		    height: 15px;
		    width: 15px !important;
		    vertical-align: middle;
		}

	</style>
	<script type="text/javascript">
		<?php
			$s = '';
			$e = '';
			$dates = [];

			if( sizeof($schedules) ) {
				$tz = Settings::get('TIME_ZONE');
				$dateTimeFormat = config('jp.date_time_format');
				foreach ($schedules as $key => $schedule) {
					$s = convertTimezone($schedule->start_date_time, $tz);
					$e = convertTimezone($schedule->end_date_time, $tz);
					$dates[$key]['start_date_time'] = $s->format($dateTimeFormat);
					$dates[$key]['end_date_time']   = $e->format($dateTimeFormat);;
				}
			}

			$dates = json_encode($dates);

			$greenSkysetting = Settings::get('GREEN_SKY');
			$greenSkyConnected = ine($greenSkysetting, 'greensky_enabled');

 			$job->address->state = $job->address->state;
			$jobToView = [
				"financial_details" => $job->financial_calculation,
				"address" => $job->address,
				"id" => $job->id,
				"share_token" => $job->share_token,
				"customer" => [
					"first_name" => $job->customer->first_name,
					"last_name" => $job->customer->last_name,
					"email" => $job->customer->email,
					"phones" => $job->customer->phones,
					"id" => $job->customer->id
				]
			];
		?>
		var scheduleDates = '<?php echo $dates;  ?>';

		var job =  {};//'<?php //echo $job; ?>';
		var jobToView =  <?php echo json_encode($jobToView);  ?>
	</script>
	<style type="text/css">
		.resource-inner-section .bx-wrapper .bx-viewport {
			left: 0;
		}
		.proposals-contractors .resource-viewer .img-thumb img {
			height:auto !important;
			width:100% !important;
			display:inline-block;
		}
		.proposals-contractors .bx-wrapper .bx-viewport {
			width:102% !important;
			height: auto !important;
			border-bottom: 1px solid #fff;
		}
		.public-customer .proposals-contractors .bx-wrapper .bx-prev {
			left:5px;
		}
		.public-customer .proposals-contractors .bx-wrapper .bx-next {
			/*right:0;*/
		}
		.proposals-contractors .resource-viewer .resource-inner {
			width:95%;
		}
		.proposals-contractors .resource-viewer .web-update-icon {
			left: 0;
			right: auto;
			top: 0;
			border-bottom-left-radius: 0;
			border-bottom-right-radius: 84%;
			height: 16px;
			padding: 1px 9px;
			position: absolute;
			width: 18px;
		}
		.proposals-contractors .resource-viewer .estimate-icon-es {
		    background: #ddd none repeat scroll 0 0;
		    border-bottom-left-radius: 84%;
		    color: #666;
		    height: 24px;
		    padding: 0;
		    position: absolute;
		    right: 0;
		    top: 0;
		    width: 26px;
		    z-index: 999;
		}
		.proposals-contractors .resource-viewer .estimate-icon-es img {
			margin-left: 6px;
		}
		.proposals-contractors .resource-viewer .web-update-icon.colored-icon {
			background-color: #999933;
		}
		.proposal-legends {
			width: 100%;
			margin-bottom: 5px;
		}
		.proposal-legends div {
			display: inline-block;
			text-transform: none;
			font-size: 14px;
			margin-right: 5px;
			width:44%;
			text-align: left;
		}
		.proposal-legends span {
			height: 10px;
			width: 10px;
			display: inline-block;
		}
		.scheduled span {
			background: #428bca;
		}
		.scheduled-border {
			border: 1px solid #428bca !important;
		}
		.viewed span {
			background: #333399;
		}
		.sent span {
			background: #FFA500;
		}
		.accepted span {
			background: green;
		}
		.rejected span {
			background: #c81b1b;
		}
		.draft span {
			background: #999933;
		}
		.viewed-border {
			border: 1px solid #333399 !important;
		}
		.sent-border {
			border: 1px solid #FFA500 !important;
		}
		.accepted-border {
			border: 1px solid green !important;
		}
		.rejected-border {
			border: 1px solid #c81b1b !important;
		}
		.draft-border {
			border: 1px solid #999933 !important;
		}
		.weather-module .weather-wrap{
			border: 1px solid #ccc;
			padding: 10px 0px;
		}
		.weather-module .weather-wrap .weather-address{
			padding-left: 0px;
		}
		.weather-module .weather-wrap .sun,
		.weather-module .weather-wrap .weather-address{
			text-align: left;
			font-size:16px;
			line-height:1 !important;
		}
		.weather-module .weather-wrap .weather-address span {
			font-size: 10px;
			padding: 0px;
		}
		.weather-module .weather-wrap .weather-days {
			font-size: 10px;
			width:100%;
			margin-top: 15px;
			float: left;
		}
		.weather-module .weather-wrap .weather-days .weather-day{
			font-size: 10px;
			width:30%;
			display: inline-block;
		}
		.weather-module .weather-wrap .weather-days  img{
			width: 30px;
			margin: 5px 0px;			
		}
		.datepicker table tr td{
			background-color: #eee;
			color: #666;
		}
		.low-opacity {
			opacity: 0.4 !important;
			cursor: default !important;
		}
		.proposal-legends{
			display: inline-block;
			z-index: -1;
			text-align: left;
			padding-left: 15px;
		}
		.proposal-status-info{
			font-size:15px;
			float:right;
		}
		.proposal-status-info:hover ~ .proposal-legends {
			display: none;
		}
		.proposals-contractors .bx-wrapper .bx-viewport {
			box-shadow: none;
		}
		.dropdown-menu .divider{
			margin: 0px ;
		}
		.dropdown-menu > li > a {
			white-space: normal;
			text-align: left;
		}
		.page-feilds{
			padding: 5px 10px;
			font-size: 15px;
			color: #555;
		}
		.proposals-contractors .tooltip.top{
			padding: 0;
		}
		.proposals-contractors .tooltip.top .tooltip-arrow{
			border-top-color: #efefef;
			border-width-color: 6px;
		}
		.proposals-contractors .tooltip{
			box-shadow: rgba(0, 0, 0, 0.3) 0 2px 10px;
		}
		.proposals-contractors .tooltip-inner {
			color: #000;
			background: #efefef;
			width: 250px;
			padding: 10px 0;
			border-bottom-color: #efefef;
		}
		
		.proposal-legends div {
			margin: 5px;
		}
		.msg-alerts {
			position: fixed;
			margin: 20px;
			z-index: 9;
			top: 0;
			right: 0;
		}
		.msg-alerts button.close {
			display: none;
		}
		.datepicker table tr td.disabled {
			color: #666;
		}
		.datepicker table tr td.disabled:hover {
			color: #666;
		}
		.datepicker table tr td.old.disabled {
			color: #999;
		}
		.datepicker table tr td.new.disabled {
			color: #999;
		}
		.datepicker table tr td.old.disabled:hover {
			color: #999;
		}
		.datepicker table tr td.new.disabled:hover {
			color: #999;
		}
		.datepicker table tr td.disabled.day-select {
			background-color: #428bca;
			border-color: #428bca;
			color: #fff;
			border-radius: 0;
		}
		.datepicker table tr td.disabled.day-select:hover {
			color: #fff;
		}
		.bx-wrapper .bx-controls-direction a {
			z-index: 9;
		}
		.table {
			border: 1px solid #ccc;
			table-layout: fixed;
		}
		.btn-group.open .dropdown-toggle {
		    -webkit-box-shadow: none;
		    box-shadow: none;
		}
		.bx-wrapper .bx-pager.bx-default-pager a {
			height: 5px;
			width: 5px;
		}
		.container.main-container .col-md-6.customer-resource-section.main-section-col {
			float: left;
		}
		.public-customer .main-section-col {
			float: left !important;
		}
		.day-change .fc-row .fc-content-skeleton td {
			color: #fff;
		}
		.fc-row .fc-content-skeleton td {
		}
		.main-resource-img img {
			max-height: 100%;
   			width: auto;
   			height: auto;		
   		}
   		.resource-slider img {
   			height: 120px;
   			width: auto;			
   		}
   		.resource-inner-section.new-sec .bx-wrapper .bx-viewport {
   			height: 130px !important;
   		}
   		.resource-inner-section.new-sec .bx-wrapper .bx-prev {
   			left: -40px;
   		}
   		.resource-inner-section.new-sec .bx-wrapper .bx-next {
   			right: -40px;
   		}
   		.resource-inner-section.new-sec .bx-wrapper .bx-viewport a { 
   			display: inline-block;
   			text-align: center;
   		}
   		.resource-inner-section.new-sec .bx-wrapper .bx-viewport a img {
   			display: inline-block;
   		}
   		.poweredby-logo {
   			margin: 10px 30px;
   			width: 250px;
   		}
   		.public-customer .social-icons {
   			padding-top: 36px;
   		}
   		.public-customer .social-icons a {
   			margin-right: 5px;
		    background: #888;
		    height: 25px;
		    width: 25px;
		    display: inline-block;
		    text-align: center;
		    border-radius: 50%;
		    color: #fff;
		    line-height: 26px;
		    font-size: 12px;
   		}
   		.public-customer header .logo {
   			display: inline-block;
   			max-width: 215px;
   			width: auto;
   		}
   		.public-customer .customer-resource-section .main-resource-img {
		    margin-bottom: 10px;
		    text-align: center;
		    width: 100%;
		    height: 350px;
			line-height: 330px;
		}
		.slider-bg-color {
			background: #f9f9f9;
		    border-radius: 4px;
		    padding: 10px;
		}
   		.job-description p {
   			display: inline-block;
   			margin-right: 20px;
   		}
   		.nav {
   			text-align: center;
   		}
   		.nav-tabs>li {
   			display: inline-block;
   			float: none;
   		}
   		.nav-tabs.nav>li>a {
   			padding: 10px 9px;
   			margin-right: 0;
   		}
   		@media(max-width: 991px) and (min-width: 767px) {
   			.weather-module .weather-wrap .weather-days {
   				margin-top: 0;
   			}
   		}
   		@media(max-width: 991px) {
			.resource-inner-section.new-sec {
			    border-bottom: 1px solid #eee;
			    margin-bottom: 10px;
			    padding-bottom: 30px;
			}
			body .fc {
				border-bottom: 1px solid #eee;
				border-top: 1px solid #eee;
				padding-bottom: 30px;
				padding-top: 30px;
				margin-bottom: 30px;
				margin-top: 30px;
			}
			.main-resource-img {
			    border-top: 1px solid #eee;
			}
			.proposals-contractors.text-left {
			    border-top: 1px solid #eee;
			    display: block;
			    padding-top: 15px;
			}
   		}
   		@media(max-width: 767px) {
			.public-customer header .working-details h1 {
				font-size: 26px;
				margin-top: 0;
			}
			.public-customer header .contact-info {
				padding: 0;
			}
			.customer-resource-section .btn {
				margin-bottom: 10px;
			}
			footer .col-sm-6 {
				text-align: center;
			}
			.weather-module .weather-wrap .sun, .weather-module .weather-wrap .weather-address {
				padding-left: 15px;
			}
			.resource-inner-section.new-sec .bx-wrapper .bx-next {
				right: -12px;
			}
			.resource-inner-section.new-sec .bx-wrapper .bx-prev {
				left: -12px;
			}
		}
		@media(max-width: 480px) {
			header .col-xs-4 {
				width: 100%;
			}
			header .contact-info {
				text-align: center;
			}
			.public-customer header .logo {
				display: inline-block;
				max-height: 100%;
				width: 100px;
			}
			.public-customer header .logo.brand > img {
				display: inline-block;
				vertical-align: middle;
			}
			.poweredby-logo {
				padding: 10px;
				margin: 0;
			}
			body.public-customer header .job-id-section {
		        display: block;
		    }
		    body.public-customer header .job-id-section .btn-group {
		        width: 100%;
		    }
		    body.public-customer header .job-id-section .btn-group .dropdown-menu {
		        width: 100% !important;
		    }
		    body.public-customer .proposals-contractors.text-left {
		        text-align: center;
		    }
		    body.public-customer .working-details {
		        margin-top: 15px;
		    }
		    body.public-customer .customer-resource-section .main-resource-img {
		        height: 250px;
		        line-height: 270px;
		    }
		}
		@media(max-width: 992px) {
		    .proposals-contractors .tooltip.top {
		        right: 10px;
		        left: auto !important;
		    }
		    .proposals-contractors .tooltip.top .tooltip-arrow {
		        bottom: -5px;
		    }
		}
		@media(max-width: 992px) {
		    .customer-resource-section .calendar {
		        float: none !important;
		    }
		    .public-customer .customer-resource-section .main-resource-img {
		        padding-top: 10px;
		    }
		}
		@media(max-width: 1199px) {
		    .public-customer .proposals-contractors {
		        text-align: center;
		    }
		    .customer-resource-section .calendar .fc-toolbar .fc-center h2 {
		        font-size: 25px;
		        margin-left: 15px;
		        margin-bottom: 5px;
		    }
		    .public-customer .proposals-contractors .resource-viewer {
		       margin-left: auto;
		       margin-right: auto;
		    }
		}
		@media(max-width: 1199px) and (min-width: 992px) {
		    .customer-resource-section .calendar .fc-toolbar {
		        text-align: left;
		    }
		    .nav-tabs.nav>li>a {
		    	padding-left: 6px;
		    	padding-right: 6px;
		    	font-size: 12px;
		    }
		}
		@media(max-width: 767px) {
		    .customer-project-detail-table .table>thead>tr>th,
		    .customer-project-detail-table .table>tbody>tr>th,
		    .customer-project-detail-table .table>thead>tr>td,
		    .customer-project-detail-table .table>tbody>tr>td {
		        width: 150px;
		        white-space: normal;
		    }
		}
		@media(max-width: 480px) {
		    .customer-resource-section .calendar .fc-toolbar .fc-center h2 {
		        font-size: 18px;
		        margin-top: 8px;
		    }
		}
		.page-feilds {
		    border: 1px solid #ccc;
		}
		.modal-3d-container {
			position: relative;
			transition: 0.3s ease-in-out;
		}
		.modal-3d-container img {
			transition: 0.3s ease-in-out;
		}
		.modal-3d-container a {
			position: absolute;
		    display: flex;
		    align-items: center;
		    justify-content: center;
		    border: 0;
		    right: 12px;
		    top: 12px;
		    z-index: 11;
		    transition: 0.3s ease-in-out;
		    border-radius: 4px;
		    padding: 5px 10px;
		    background: rgb(0 0 0 / 30%);
			text-decoration: none;
		}
		.modal-3d-container a svg {
			width: 25px;
		    height: auto;
		    fill: #fff;
		    margin-right: 5px;
		}
		.modal-3d-icon {
			position: absolute;
		    display: flex;
		    align-items: center;
		    justify-content: center;
		    background: transparent;
		    border: 0;
		    left: 28%;
		    top: 28%;
		    z-index: 11;
		    transition: 0.3s ease-in-out;
		    border-radius: 4px;
		   	background: rgb(0 0 0 / 46%);
   			padding: 6px;
		}
		.modal-3d-icon svg {
			width: 45px;
		    height: auto;
		    fill: #fff;
		}
		.modal-3d-container a:hover {
			background: rgb(0 0 0 / 50%);
		}
		.modal-3d-container a span {
			font-weight: normal;
			color: #fff;
		}
	</style>
</head>
<body class="public-customer" ng-app="jobProgress" ng-controller="customerPageCtrl as Ctrl">
	<div class="msg-alerts" ng-if="message.type">
		<alert type="success" ng-if="message.type == 'success'"><span ng-bind="message.message"></span></alert>
		<alert type="danger" ng-if="message.type == 'error'"><span ng-bind="message.message"></span></alert>
	</div>
	<header>
		<div class="container">
			<div class="row">
				<div class="col-xs-4 logo-col">
					<div class="logo brand">
						@if($job->company->logo)
							<img src="{{FlySystem::getUrl(config('jp.BASE_PATH').$job->company->logo)}}" alt="Company">
						@else
							<span>{{ $job->company->name ?? '' }}</span>
						@endif
					</div>
				</div>
				<div class="col-xs-4">
					<div class="working-details">
						<h1>Work Progress</h1>
						<div class="job-id-section">
							<p>
								<div class="btn-group">
								Job-Id:
									<span type="button" class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="cursor:pointer;">
										{{$job->name ? $job->name : $job->number}} <span class="caret"></span>
									</span>

									<ul 
										class="dropdown-menu" 
										style="max-height:200px;overflow:auto;width:300px;">
										@foreach($jobsList as $token => $list)
											<li>
												<a href="{{config('app.url').'customer_job_preview/'.$list->share_token}}">
													@if($list->name)
														{{ $list->name }}
													@else
														{{$list->number}}
														<?php  $trades = $list->trades->pluck('name')->toArray(); ?>
														@if(!empty($trades))
														{{ ' / ' . implode(' / ', $trades) }}
														@endif
													@endif	
												</a>
											</li>
											<li role="separator" class="divider"></li>
										@endforeach
									</ul>
								</div>
							</p>
						</div>
					</div>
				</div>
				<div class="col-xs-4">
					<div class="contact-info text-right">
						<p>Contact Us</p>
						@if($job->company->office_phone)
							<p>
								{{ phoneNumberFormat($job->company->office_phone,$job->company->country->code) }}
							</p>
						@endif
						@if($customerRep)
						<div class="customer-rep-info">
							<?php
							$repProfile = $customerRep->profile; 
							$phone = $repProfile->phone;
						 	if(!$phone 
						 		&& !empty($phones = $repProfile->additional_phone)) {
						 		$repPhone = reset($phones);
						 		$phone = $repPhone->phone;
						 	}
							 ?>
							<p>Salesman / Customer Rep: <span>{{ $customerRep->full_name }}</span></p>
							<p>Email: <span>{{ $customerRep->email }}</span></p>
							@if($phone)
							<p>Cell #: <span> {{ phoneNumberFormat($phone, $job->company->country->code) }} </span></p>
							@endif
						</div>
						@endif
						@if($socialLinks['value'])
						<div class="third-party-icons">
							@if($socialLinks['value']['facebook'])
								<a href="{{$socialLinks['value']['facebook']}}"
									target="_blank" 
									data-toggle="tooltip"
									data-placement="bottom"
									class="{{$socialLinks['value']['facebook'] ? '1234' : 'low-opacity'}}"
									title="Facebook">
									<img src="{{config('app.url')}}icon/facebook.png" alt="Facebook">
								</a>
							@endif
							@if($socialLinks['value']['twitter'])
								<a href="{{$socialLinks['value']['twitter']}}"
									target="_blank"
									data-toggle="tooltip"
									data-placement="bottom"
									class="{{$socialLinks['value']['twitter'] ? '' : 'low-opacity'}}"
									title="Twitter">
									<img src="{{config('app.url')}}icon/twitter.png" alt="Twitter">
								</a>
							@endif
							@if($socialLinks['value']['google_plus'])
								<a href="{{$socialLinks['value']['google_plus']}}"
									target="_blank"
									data-toggle="tooltip"
									data-placement="bottom"
									class="{{$socialLinks['value']['google_plus'] ? '' : 'low-opacity'}}"
									title="Google Plus">
									<img src="{{config('app.url')}}icon/google-plus.png" alt="Google Plus">
								</a>
							@endif
							@if($socialLinks['value']['linkedin'])
								<a href="{{$socialLinks['value']['linkedin']}}"
									target="_blank"
									data-toggle="tooltip"
									data-placement="bottom"
									class="{{$socialLinks['value']['linkedin'] ? '' : 'low-opacity'}}"
									title="Linkedin">
									<img src="{{config('app.url')}}icon/linkedin.png" alt="Linkedin">
								</a>
							@endif
						</div>
						@endif
						<div style="margin-top:10px;">
							@if( $job->multi_job == '1' )
							<span class="label label-warning btn multi-proj-label">Multi Project</span>
							@endif
							@if(($placeId != 'null') && ($placeId != null))
							<a class="btn btn-primary btn-xs" href="{{$googleCustomerReviewLink}}" target="_blank"> Leave a Review </a>
							@endif
						</div>
					</div>
				</div>
			</div>
		</div>
	</header>
	<div class="container main-container">
		<div class="job-description">
			<?php 
				$tr = $job->trades()->pluck('name')->toArray(); 
			?>
			@if(!empty($tr))
			<p>
				{{'Trade Type: '. implode(', ', $tr) }}
			</p>
			@endif	
			
			<?php 
				$types = $job->JobTypes()->pluck('name')->toArray();
			?>
			
			@if(!empty($types))
				<p>
					{{'Category: '. implode(', ', $types) }}
				</p>
			@endif
		</div>
		
		<div class="job-price-info customer-detail-section">
			<div class="text-center">
				<h3><span>Hi {{ $job->customer->full_name ?? '' }}!</span></h3>
				<p>{!! $job->address->present()->fullAddress !!}</p>
			</div>
			<div class="row parent-job-container">
				<div class="col-md-6">
					<div class="cust-inner-detail text-left">

						<p>
							<label>Job Price:</label>
							<span>
								@if($job->multi_job == '1')
									{{$job->financialCalculation->total_job_amount ?  currencyFormat($job->financialCalculation->total_job_amount) : '-- --' }}
								@else
									{{$job->amount ? currencyFormat($job->amount) : '-- --' }}
								@endif
							</span>
							@if($job->multi_job != '1')
							<div class="sub-inner-detail">
								<p>
									<label>Tax:</label>
									<span>{{$job->tax_rate ? numberFormat($job->tax_rate).'%' : '-- --'}}</span>
								</p>
							</div>
							@endif
							<div class="sub-inner-detail inner-detail-border">
								<p>
									<label>Total Amount:</label>
									<span>
										{{$job->financialCalculation->total_job_amount ? currencyFormat($job->financialCalculation->total_job_amount) : '-- --' }}
									</span>
								</p>
							</div>
						</p>
						<p>
							<label>Change Orders:</label>
							@if(!($job->financialCalculation->can_block_financials) 
								&& $job->financialCalculation->total_change_order_amount != '0.00')
								<span>
									{{currencyFormat($job->financialCalculation->total_change_order_amount)}}
								</span>
							@else
								<span>-- --</span>
							@endif
						</p>
						<p>
							<label>Payment Received:</label>
							@if(!($job->financialCalculation->can_block_financials) 
								&& $job->financialCalculation->total_received_payemnt != '0.00')
								<span>
									{{currencyFormat($job->financialCalculation->total_received_payemnt)}}
								</span>
							@else
								<span>-- --</span>
							@endif
						</p>
						<p>
							<label>Credit:</label>
							@if(!($job->financialCalculation->can_block_financials)
								&& $job->financialCalculation->total_credits != '0.00')
								<span>
									{{currencyFormat($job->financialCalculation->total_credits)}}
								</span>
							@else
								<span>-- --</span>
							@endif
						</p>

						<?php
							$ownd_amount = 0;
							$amou = $job->financialCalculation->total_change_order_amount + $job->financialCalculation->total_job_amount;
							if( $job->financialCalculation->total_credits > 0 ) {
								$pay = $job->financialCalculation->total_received_payemnt + $job->financialCalculation->total_credits;
								$ownd_amount = $amou - $pay;
							} else {
								$ownd_amount = $job->financialCalculation->pending_payment;
							}
						?>

						<p>
							<label>Amount Owed:</label>
							@if(!($job->financialCalculation->can_block_financials)
								&& $ownd_amount != '0.00')
								<span>
									@if($ownd_amount < 0)
										-${{currencyFormat(abs($ownd_amount),2)}}
									@else
										${{currencyFormat(abs($ownd_amount),2)}}
									@endif
								</span>
							@else
								<span>
									-- --
								</span>
							@endif
						</p>
					</div>
				</div>
				<div class="col-md-6">
					<div class="proposals-contractors text-left parent-proposal-container">
						<h4 style="font-weight: 400;"><span style="font-weight: bold;">Forms / Proposals</span>
							<span
								class="proposal-status-info"
								tooltip-placement="top"
								tooltip-html-unsafe="<% proposalTooltip %>">
								<i class="fa fa-info-circle"></i>
							</span>
						</h4>

						<div class="proposal-tab-container">
							<ul class="nav nav-tabs">
							    <li  class="active"><a data-toggle="tab" href="#parent-pending-proposal">Pending</a></li>
							    <li><a data-toggle="tab" href="#parent-accepted-proposal">Accepted</a></li>
							    <li><a data-toggle="tab" href="#parent-rejected-proposal">Rejected</a></li>
							</ul>
							<?php $appUrl = config('app.url'); ?>
							<div class="tab-content">

								<!-- pending proposals -->
								<div id="parent-pending-proposal" class="tab-pane fade in active">
									@if(!$pendingProposals->count())
										<div style="padding: 30px 0;text-align: center;">
											No pending proposal.
										</div>
									@endif
									@foreach($pendingProposals as $proposal)
										@include('partials.customer_web_page_proposal_nav', ['proposal'=> $proposal])
									@endforeach
								</div>

								<!-- accepted proposals -->
								<div id="parent-accepted-proposal" class="tab-pane fade">
									@if(!$acceptedProposals->count())
										<div style="padding: 30px 0;text-align: center;">
											No accepted proposal.
										</div>
									@endif
									@foreach($acceptedProposals as $proposal)
										@include('partials.customer_web_page_proposal_nav', ['proposal'=> $proposal])
									@endforeach
								</div>
								<!-- rejected proposals -->
								<div id="parent-rejected-proposal" class="tab-pane fade">
									@if(!$rejectedProposals->count())
										<div style="padding: 30px 0;text-align: center;">
											No rejected proposal.
										</div>
									@endif
									@foreach($rejectedProposals as $proposal)

										@include('partials.customer_web_page_proposal_nav', ['proposal'=> $proposal])
									@endforeach
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="proposal-tab-container project-tabs">
			<ul class="nav nav-tabs">
				@foreach($job->projects as $index => $project)
			    <li class="{{ $index == 0 ? 'active' : '' }}"><a data-toggle="tab" data-href="#{{ $project->number }}" href="#{{ $project->number }}">{{ $project->number }}</a></li>
			    @endforeach
			</ul>
			<div class="tab-content">
				@foreach($job->projects as $index => $currentJob)

				<?php
					$dates  = [];

					$currentJob->address = $job->address;

					if( sizeof($currentJob->schedules) ) {
						$tz = Settings::get('TIME_ZONE');
						$dateTimeFormat = config('jp.date_time_format');
						foreach($currentJob->schedules as $key => $schedule) {
							$s = convertTimezone($schedule->start_date_time, $tz);
							$e = convertTimezone($schedule->end_date_time, $tz);

							$dates[$key]['start_date_time'] = $s->format($dateTimeFormat);
							$dates[$key]['end_date_time']   = $e->format($dateTimeFormat);
						}
					}
				?>

				<script type="text/javascript">
					var currentProject = <?php echo $currentJob ?>
				</script>
				<div id="{{ $currentJob->number }}" class="row tab-pane fade in {{ $index == 0 ? 'active' : '' }}">
					<div class="col-md-3 section-border-right main-section-col">
						<div class="customer-detail-section text-center">
							<!-- <h3><span>Hi {{ $currentJob->customer->full_name ?? '' }}!</span></h3>
							<p>{!! $currentJob->address->present()->fullAddress !!}</p> -->
							<div class="cust-inner-detail text-left">

								<p>
									<label>Job Price:</label>
									<span>
										@if($currentJob->multi_job == '1')
											{{$currentJob->financialCalculation->total_job_amount ?  currencyFormat($currentJob->financialCalculation->total_job_amount) : '-- --' }}
										@else
											{{$currentJob->amount ? currencyFormat($currentJob->amount) : '-- --' }}
										@endif
									</span>
									@if($currentJob->multi_job != '1')
									<div class="sub-inner-detail">
										<p>
											<label>Tax:</label>
											<span>{{$currentJob->tax_rate ? numberFormat($currentJob->tax_rate).'%' : '-- --'}}</span>
										</p>
									</div>
									@endif
									<div class="sub-inner-detail inner-detail-border">
										<p>
											<label>Total Amount:</label>
											<span>
												{{$currentJob->financialCalculation->total_job_amount ? currencyFormat($currentJob->financialCalculation->total_job_amount) : '-- --' }}
											</span>
										</p>
									</div>
								</p>
								<p>
									<label>Change Orders:</label>
									@if(!($currentJob->financialCalculation->can_block_financials) 
										&& $currentJob->financialCalculation->total_change_order_amount != '0.00')
										<span>
											{{currencyFormat($currentJob->financialCalculation->total_change_order_amount)}}
										</span>
									@else
										<span>-- --</span>
									@endif
								</p>
								<p>
									<label>Payment Received:</label>
									@if(!($currentJob->financialCalculation->can_block_financials) 
										&& $currentJob->financialCalculation->total_received_payemnt != '0.00')
										<span>
											{{currencyFormat($currentJob->financialCalculation->total_received_payemnt)}}
										</span>
									@else
										<span>-- --</span>
									@endif
								</p>
								<p>
									<label>Credit:</label>
									@if(!($currentJob->financialCalculation->can_block_financials) 
										&& $currentJob->financialCalculation->total_credits != '0.00')
										<span>
											{{currencyFormat($currentJob->financialCalculation->total_credits)}}
										</span>
									@else
										<span>-- --</span>
									@endif
								</p>

								<?php
									$ownd_amount = 0;
									$amou = $currentJob->financialCalculation->total_change_order_amount + $currentJob->financialCalculation->total_job_amount;
									if( $currentJob->financialCalculation->total_credits > 0 ) {
										$pay = $currentJob->financialCalculation->total_received_payemnt + $currentJob->financialCalculation->total_credits;
										$ownd_amount = $amou - $pay;
									} else {
										$ownd_amount = $currentJob->financialCalculation->pending_payment;
									}
								?>
								<p>
									<label>Amount Owed:</label>
									@if(!($currentJob->financialCalculation->can_block_financials)
										&& $ownd_amount != '0.00')
										<span>
											@if($ownd_amount < 0)
												-${{currencyFormat(abs($ownd_amount),2)}}
											@else
												${{currencyFormat(abs($ownd_amount),2)}}
											@endif
										</span>
									@else
										<span>
											-- --
										</span>
									@endif
								</p>
							</div>
							<!-- hide remaining balance if job is not awarded -->
							@if(!$currentJob->canBlockFinacials())
							<div class="remaining-balance text-center">
								@if( $currentJob->financialCalculation->pending_payment
									&& $currentJob->financialCalculation->pending_payment != '0.00' )
									<p>Remaining Balance</p>
									<h3>
										@if( $currentJob->financialCalculation->pending_payment < 0 )
											-${{currencyFormat(abs($currentJob->financialCalculation->pending_payment),2)}}
										@else
											${{currencyFormat(abs($currentJob->financialCalculation->pending_payment),2)}}
										@endif
									</h3>
								@else
								 -- --
								@endif
								@if( !($currentJob->isMultiJob()) && $currentJob->jobInvoices->count() )
									<?php
									$jsJob = [
										'id'     => $currentJob->id,
										'number' => $currentJob->number,
										'trades' => $currentJob->trades,
										'multi_job' => (bool)$currentJob->multi_job,
										'parent_id' => $currentJob->parent_id,
									];
									?>
									<div class="text-center">
										<div class="btn-group">
											<button type="button" class="btn btn-primary btn-sm dropdown-toggle view-invoices"
												ng-click='Ctrl.viewInvoices( {{ json_encode($jsJob) }} )'>
												View Invoice
											</button>
										</div>
									</div>
								@endif
								@if( $job->financialCalculation->pending_payment 
									&& $job->financialCalculation->pending_payment != '0.00' 
									&& $greenSkyConnected)

 									<div ng-if="Ctrl.jobToView.id" style="margin-top: 15px;">
										<green-sky job="Ctrl.jobToView" project="{
											id: '<?php echo $currentJob->id; ?>', 
											amount: '<?php echo $currentJob->financial_calculation->pending_payment; ?>',
											share_token: '<?php echo $currentJob->share_token; ?>'
										}"></green-sky>
									</div>
								@endif
							</div>
							@endif
							@if(!$currentJob->isMultiJob() && !$currentJob->jobInvoices->count())
								<div class="alert alert-info" style="padding:10px;margin-top:20px;" role="alert">
									{{ trans('response.error.invoice_not_created') }}
								</div>
							@endif
							@if($currentJob->sharedProposals->count())
								<div class="proposals-contractors text-left">
									<h4>Forms / Proposals
										<span
											class="proposal-status-info"
											tooltip-placement="top"
											tooltip-html-unsafe="<% proposalTooltip %>">
											<i class="fa fa-info-circle"></i>
										</span>
									</h4>
									<div class="proposal-tab-container">
										<ul class="nav nav-tabs">
										    <li  class="active"><a data-toggle="tab" href="#pending-proposal">Pending</a></li>
										    <li><a data-toggle="tab" href="#accepted-proposal">Accepted</a></li>
										    <li><a data-toggle="tab" href="#rejected-proposal">Rejected</a></li>
										</ul>
										<?php $appUrl = config('app.url'); ?>
										<div class="tab-content">

											<!-- pending proposals -->
											<div id="pending-proposal" class="tab-pane fade in active">
											@if(!$pendingProposals->count())
												<div style="padding: 30px 0;text-align: center;">
													No pending proposal.
												</div>
											@endif
											@foreach($pendingProposals as $proposal)
												@include('partials.customer_web_page_proposal_nav', ['proposal'=> $proposal])
											@endforeach
											</div>

											<!-- accepted proposals -->
											<div id="accepted-proposal" class="tab-pane fade">
											@if(!$acceptedProposals->count())
												<div style="padding: 30px 0;text-align: center;">
													No accepted proposal.
												</div>
											@endif
											@foreach($acceptedProposals as $proposal)
												@include('partials.customer_web_page_proposal_nav', ['proposal'=> $proposal])
											@endforeach
											</div>
											<!-- rejected proposals -->
											<div id="rejected-proposal" class="tab-pane fade">
											@if(!$rejectedProposals->count())
												<div style="padding: 30px 0;text-align: center;">
													No rejected proposal.
												</div>
											@endif
											@foreach($rejectedProposals as $proposal)

												@include('partials.customer_web_page_proposal_nav', ['proposal'=> $proposal])
											@endforeach
											</div>

											<!-- rejected proposals -->
											<div id="rejected-proposal" class="tab-pane fade">
											@if(!$rejectedProposals->count())
												<div style="padding: 30px 0;text-align: center;">
													No rejected proposal.
												</div>
											@endif
											@foreach($rejectedProposals as $proposal)

												@include('partials.customer_web_page_proposal_nav', ['proposal'=> $proposal])
											@endforeach
											</div>
										</div>
									</div>
								</div>
							@endif
							@if($sharedEstimates->count())
								<div class="proposals-contractors text-left" >
									<h4 style="margin-bottom: 0">Estimating</h4>
									<?php $appUrl = config('app.url'); ?>
									<div id="estimate" class="">
										@foreach($sharedEstimates as $estimate)
										<div style="margin:10px 3px -2px 0"  class="resource-viewer">
											<div class="resource-inner">
												@if($estimate->type === \App\Models\Estimation::EAGLE_VIEW)
													<span class="estimate-icon-es" style="background: #ccc"><img class="evicon" src="https://www.jobprogress.com/app/img/evlogo-1.png" alt=""></span>
												@elseif($estimate->type === \App\Models\Estimation::SKYMEASURE)
												<span class="estimate-icon-es" style="background: #ccc"><img class="evicon" src="https://www.jobprogress.com/app/img/skymeasure-icon.png" alt=""></span>
												@endif
												<a href="{{FlySystem::getUrl(config('jp.BASE_PATH').$estimate->file_path)}}" class="img-col" download target="blank">
													<div class="img-thumb">
														<img src="{{getFileIcon($estimate->file_mime_type, $estimate->file_path)}}" alt="resource">
													</div>
													<span class="file-name">
														{{ $estimate->title ?? '' }}
													</span>
												</a>
											</div>
										</div>
										@endforeach
									</div>
								</div>
							@endif
						</div>
					</div>
					<div class="col-md-6 customer-resource-section main-section-col">
						@if(count($resources['images']))
							<div class="main-resource-img slider-bg-color">
								<img src="{{ $resources['images']['0']->url }}" alt="slide1" id="main-resource">
							</div>
							@if(count($resources['images']) > 1)
								<div class="resource-inner-section new-sec">
									<div class="resource-slider">
										@foreach($resources['images'] as $image)
										<a href="#" onclick="return false;">
											<img id="slide1" src="{{$image->url}}" alt="slide1" />
										</a>
										@endforeach
									</div>
								</div>
							@endif
							<div class="clearfix"></div><br>
						@endif
						<div class="clearfix"></div>
						<div class="text-center row  ">
							<div class="col-md-6 col-sm-6 col-xs-12">
								<a
									href="javascript:void(0)"
									class="btn btn-success btn-block"
									ng-click="Ctrl.comment('testimonial')">
									Testimonial
								</a>
							</div>
							<div class="col-md-6 col-sm-6 col-xs-12">
								<a
									href="javascript:void(0)"
									class="btn btn-danger btn-block"
									ng-click="Ctrl.comment('complaint')">
									Contact Us / Issues
								</a>
							</div>
						</div>
						<div class="clearfix"></div><br>
					    <div class="form-group">
					        <div class="row">
					            <div class="col-md-12">
					            	@if(!$currentJob->isMultiJob())
					            		@if($schedules->count())
							        	<div class="proposal-legends" style="float:right;width:125px;margin-bottom:-50px;">
							        		<div class="scheduled" style="width:100%; font-size: 12px;">
							        			<span></span> Job Scheduled
							        		</div>
							        	</div>
							        	@else
						            	<div>
						            		<div class="alert alert-info" style="padding:10px;text-align:center;" role="alert">
												Job yet to be scheduled.
											</div>
						            	</div>
						            	@endif
						        	@endif

					                <div class="calendar multi_job" dates-ref='<?php echo json_encode($dates); ?>'  style="float:left;"></div>
					            </div>
					        </div>
					    </div>
					</div>
					<div class="col-md-3 section-border-left main-section-col">
						<div class="customer-activity-section">
							<div class="customer-activity-feed">
								<h4 class="text-center">What's Happening</h4>
								<ul>
									<li>
										<span class="pull-right badge">{{ $counts['messages'] }}</span>
										Messages
									</li>
									<li>
										<span class="pull-right badge">{{ $counts['upcoming_tasks'] }}</span>
										Upcoming Tasks
									</li>
									<li>
										<span class="pull-right badge">{{ $counts['emails'] }}</span>
										Emails
									</li>
								</ul>
								@if(count($resources['files']))
									<div class="proposals-contractors text-center">
										<h4>Important Documents</h4>
										<div class="resource_slider">
											@foreach($resources['files']->chunk(2) as $files)
												<div>
													<div class="resource-viewer resource-document">
														<div class="resource-inner">
															<a href="{{ $files[0]->url }}" download = "{{ $files[0]->name }}" class="img-col" target="_blank">
																<div class="img-thumb">
																	<img src=" {{getFileIcon($files[0]->mime_type, $files[0]->path)}}" alt="resource">
																</div>
																<span  class="file-name">
																	{{ $files[0]->name ?? '' }}
																</span>
																</a>
															</a>
														</div>
													</div>
													@if(count($files) > 1)
													<div class="resource-viewer resource-document">
														<div class="resource-inner">
															<a href=" {{ $files[1]->url }}" class="img-col" target="_blank" download="{{ $files[1]->name }}">
																<div class="img-thumb">
																	<img src="{{getFileIcon($files[1]->mime_type, $files[1]->path)}}" alt="resource">
																</div>
																<span  class="file-name">
																	{{ $files[1]->name ?? '' }}
																</span>
															</a>
														</div>
													</div>
													@endif
												</div>
											@endforeach
										</div>
									</div>
								@endif
								<div class="weather-module text-center">
									<br>

									@if(!empty($weather) && isset($weather['location']['city']))
									<h4>Weather</h4>
									<div class="weather-wrap">
									<div class="row">
											<div class="col-md-12 col-sm-12">
												<div class="col-md-4 col-sm-2 sun">
													<img src="http://l.yimg.com/a/i/us/we/52/{{$weather['item']['condition']['code']}}.gif">
												</div>
												<div class="col-md-8 col-sm-3 weather-address">
													<p>{{ $weather['location']['city'] ?? '' }}</p>
													<span>{{ $weather['item']['condition']['text'] ?? '' }}</span><br>
													<span>Humidity: {{ $weather['atmosphere']['humidity'] ?? '' }}%</span><br>
													<span>Wind: {{ $weather['wind']['speed'].' '.$weather['units']['speed'] }}</span><br>
												</div>
												<div class="col-md-12 col-sm-7">
													<div class="weather-days">
														@for($i = 1; $i <= 3; $i++)
															<div class="weather-day">
																<span>
																	{{ Carbon\Carbon::parse($weather['item']['forecast'][$i]['date'])->formatLocalized('%A') }}
																</span><br>
																<span>
																	<img src="http://l.yimg.com/a/i/us/we/52/{{$weather['item']['forecast'][$i]['code']}}.gif">
																</span><br>
																<span>
																	{{ $weather['item']['forecast'][$i]['low'].'&#176;'.$weather['units']['temperature'].' / '.$weather['item']['forecast'][$i]['high'].'&#176;'.$weather['units']['temperature'] }}
																</span>
															</div>
														@endfor
													</div>
												</div>
											</div>
										</div>
									</div>
									@endif
								</div>
							</div>
						</div>
						<?php $trades = $currentJob->trades; ?>

						<?php foreach($trades as $trade): ?>
							<?php
							$videosLinks = $trade->youtubeVideos;
							$videosLinks = $videosLinks->merge($youtubeVideos, $videosLinks);
							?>
							<?php if($videosLinks->isEmpty()) continue; ?>
							<div class="company-videos-section">
								<h4 class="text-center">Videos</h4>
								<div class="videos-slider-outer">
									<div class="comp-videos-slider">

										@foreach($videosLinks as $videoLink)
										<div class="comp-video-slide">
											<iframe src="https://www.youtube.com/embed/{{ $videoLink->video_id }}" frameborder="0" allowfullscreen></iframe>
										</div>
										@endforeach
									</div>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
				@endforeach
			</div>
		</div>
	</div>
	<footer>
		<div class="container">
			<div class="row">
				<div class="col-sm-6">
					<div class="social-icons">
						<a href="https://www.facebook.com/jobprogressapp" target="_blank" data-toggle="tooltip" data-placement="top" title="Facebook">
							<i class="fa fa-facebook"></i>
						</a>
						<a href="https://twitter.com/jobprogress" target="_blank" data-toggle="tooltip" data-placement="top" title="Twitter">
							<i class="fa fa-twitter"></i>
						</a>
						<a href="https://plus.google.com/u/0/b/103195463956275135204/103195463956275135204/" target="_blank" data-toggle="tooltip" data-placement="top" title="Google Plus">
							<i class="fa fa-google-plus"></i>
						</a>
						<a href="https://www.linkedin.com/company/jobprogress/" target="_blank" data-toggle="tooltip" data-placement="top" title="Linkedin">
							<i class="fa fa-linkedin"></i>
						</a>
					</div>
				</div>
				<div class="col-sm-6 text-right">
					<img class="poweredby-logo" src="{{config('app.url').'poweredby-grey-text.png'}}" alt="Powered by Jobprogress">
				</div>
			</div>
		</div>
	</footer>


	<script type="text/javascript" id="tip.html">
		//set job token
		var jobToken = '<?php echo $job->share_token; ?>';
	</script>

	<script type="text/ng-template" id="page-comment.html">
		<div class="modal-header">
			<h3 style="padding:0;" class="modal-title" ng-bind="Page.heading"></h3>
		</div>

		<div class="modal-body">
			
			<div>
				<div style=""  class="sign-container">
					<label>Subject</label>
					<input
						class="page-feilds"
						placeholder="Subject"
						type='text' 
						style="width:100%;"
						ng-model="Page.frm.subject"></textarea>
				</div>

				<div style="margin-top: 20px;"  class="sign-container">
					<label>Description</label>
					<textarea 
						class="page-feilds"
						style="height:100px;width:100%;"
						ng-model="Page.frm.description"></textarea>
				</div>
			</div>
		</div>

		<div class="modal-footer">
			<a
				href="javascript:void(0)"
				style="color:#fff;"
				class="btn btn-primary"
				type="button"
				ng-disabled="!Page.frm.subject || !Page.frm.description"
				ng-click="Page.save()">
				Send
			</a>
			<a
				href="javascript:void(0)"
				style="color:#fff;"
				class="btn btn-inverse"
				type="button"
				ng-click="Page.close()">
				Cancel
			</a>
		</div>
	</script>
	<script>
		// var initCalendar;
		{{ URL::forceScheme(config('jp.force_scheme')) }}
		var paymentPageRoute = "{{ URL::route('quickbooks.payment.page') }}";
		var quickbooksConnected = "{{ $quickbookPaymentsConnected ? 'connected' : '' }}";
		var shareTokens = [];

		@foreach($job->projects as $currentJob)
		shareTokens.push({project: "{{ $currentJob->number }}", token: "{{ $currentJob->share_token }}"});
		@endforeach
	</script>

	<script src="{{config('app.url')}}js/bootstrap.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/plugins/jquery.bxslider.min.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/moment.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/custom.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/qb-invoices.js"></script>

	<script type="text/javascript" src="{{config('app.url')}}js/components/angular/angular.min.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/components/angular-bootstrap/ui-bootstrap.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/components/angular-bootstrap/ui-bootstrap-tpls.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/components/angular-loading-bar/build/loading-bar.min.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/components/angular-validation/dist/angular-validation.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/plugins/angular-validation-rules.js"></script>

	<script type="text/javascript" src="{{config('app.url')}}js/plugins/perfect-scrollbar/js/perfect-scrollbar.jquery.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/plugins/perfect-scrollbar.js"></script>

	<script type="text/javascript" src="{{config('app.url')}}js/plugins/fullCalendar.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/plugins/fullCalendar.min.js"></script>

	<script>
	</script>
	<script type="text/javascript" src="{{config('app.url')}}js/plugins/signature.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/plugins/mask.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/window.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/app.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/config.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/models/customer.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/controllers/customer-page.controller.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/controllers/customer-page-comment.controller.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/controllers/invoice-controller.js"></script>
    <script type="text/javascript" src="{{config('app.url')}}js/app/controllers/green-sky-list.controller.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/directive/green-sky.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/factory/aside.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/service/events.js"></script>

	<!-- green sky -->
	<script src="https://www.uat.greensky.com/ecommerce/aslowas/gs-api-min.js" async="true"></script>

	<script type="text/javascript">
		$(document).ready(function(e) {
			$('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
				contentTab = $($(e.target).data('href'));
				calendar = contentTab.find('.calendar').get(0);
				initCalendar(calendar);
				reloadBxSliderForVideo();
			});
		});
	</script>
</body>
</html>