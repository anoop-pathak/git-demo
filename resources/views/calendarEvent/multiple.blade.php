<!DOCTYPE html>
<html class="no-js">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta name="viewport" content="width=device-width">
	<title> JobProgress </title>
	<link rel="stylesheet" href="{{config('app.url')}}css/main.css" />
	<style type="text/css">
		body {
			/*background: #000;*/
			color: #333;
			margin: 0;
			font-family: Helvetica,Arial,sans-serif;
			font-size: 18px;
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
		    width: 44%;
		    vertical-align: top;
		}
		.header-part .date-format {
			font-size: 13px;
			margin: 0;
			margin-top: 3px;
		}
		.header-part h1 {
			display: inline-block;
			vertical-align: top;
			width: 80%;
		}
		.clearfix {
			clear: both;
		}
		.main-logo {
			display: inline-block;
		    width: 30%;
		    text-align: right;
		    vertical-align: top;
		}
		.main-logo img {
		    opacity: 0.6; 
			width: 200px;
			margin-bottom: 10px;
		}
		.company-name {
			font-size: 14px;
		    margin-bottom: 10px;
		    margin-top: 0;
		    margin-left: 20px;
		    font-weight: bold;
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
		.filters-section .date-format {
			font-weight: bold;
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
		}
		.description {
			text-align: justify;
			white-space: pre-wrap;
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
		.table tbody tr {
			page-break-inside: avoid;
		}
		.table th {
			border-bottom: 1px solid #ccc;
		}
		.table tr.page-break:nth-child(2n+1) {
		    background-color: #f9f9f9;
		}
		.table th, .table tr.page-break td td {
			padding: 10px;
		}
		.stage i {
			font-style: normal;
		}
		.company-logo {
			border: 1px solid rgb(204, 204, 204);
			text-align: center;
			height: 130px;
			line-height: 128px;
			width: 128px;
			border-radius: 8px;
			box-sizing: border-box;
			display: inline-block;
			margin-right: 10px;
		}
		.company-logo img {
			max-width: 100%;
			max-height: 100%;
			display: inline-block;
			vertical-align: middle;
			box-sizing: border-box;
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
	  	.work-heading {
	  		/*text-align: center;*/
	  		width: 25%;
		    display: inline-block;
		    vertical-align: top;
		    margin-top: 5px;
	  	}
	  	.work-heading h2 {
	  		margin-left: 57px;
	  	}
	  	p, span{
	  		font-weight: normal;
	  	}
	  	label {
	  		color: inherit;
	  	}
	  	.no-record {
	  		margin: 50px auto;
		 	text-align: center;
		 	font-size: 30px;
		 	padding: 50px;
	  	}
	  	.job-heading {
		 	/*color: #434343;*/
		}
		.text-justify {
			text-align: justify;
		}
		/*to avoid repeating header*/
		thead, tfoot { display: table-row-group }
	</style>
</head>
<body>
	<div class="container">
		<div class="jobs-export">
			<div class="header-part">
				@if(!empty($company->logo) )
				<div class="company-logo">
					<img src="{{ FlySystem::getUrl(config('jp.BASE_PATH').$company->logo) }}" />
				</div>
				@endif
				<h1 style="width: 70%;">{{$company->name ?? ''}}</h1>
			</div>
			<div class="work-heading">
				<h2>Events</h2>
				@if(isset($filters['start_date_time']) AND isset($filters['end_date_time']))
				<div class="filters-section">
						<!-- <span class="label label-default">Default</span> -->
					<p class="date-format">
						<?php 
							$startDateFilter = new Carbon\Carbon($filters['start_date_time'], Settings::get('TIME_ZONE'));
							$endDateFilter = new Carbon\Carbon($filters['end_date_time'], Settings::get('TIME_ZONE'));
						?>
						{{ $startDateFilter->format(config('jp.date_format')) }} - {{ $endDateFilter->format(config('jp.date_format')) }}
					</p>
				</div>
				@endif
			</div>
			<div class="main-logo">
				<img src="{{asset('main-logo.png')}}">
				<div class="today-date">
					<p>
						<label>Current Date: </label>
						{{ Carbon\Carbon::now(Settings::get('TIME_ZONE'))->format(config('jp.date_format')) }}
					</p>
				</div>
				<div class="legend">
					<span></span>
				</div>
			</div>
			<div class="clearfix"></div>

			<table class="table">
				<thead>
					<tr>
						<th style="width: 4%;">#</th>
						<th style="width: 20%;">Title</th>
						<th style="width: 20%;">Work Crew</th>
						<th style="width: 10%;">Start Date</th>
						<th style="width: 10%;">End Date</th>
						<th style="width: 10%;">#Events Days</th>
						<th style="width: 10%;">Recurring</th>
					</tr>
				</thead>
				<tbody>
					<?php $key = 0 ?>
					
					@foreach ($schedules as $schedule)
					<?php
						$timeZone  = Settings::get('TIME_ZONE');
						$startDate = convertTimezone($schedule->start_date_time, $timeZone);
						$endDate   = convertTimezone($schedule->end_date_time, $timeZone);
						$diffInDays = $startDate->diffInDays($endDate);
						if($diffInDays) {
							$endDate = $endDate->subDay();
						}
						
						//only for one day
						if( $startDateFilter == $endDateFilter ) {
							if($endDate->lte($endDateFilter)) {
								continue;
							}
						}
						
					?>

					<tr class="page-break">
						<td colspan="7">
							<table style="width: 100%;">
								<tbody>
									<tr>
										<td style="width: 4%">
											{{ ++$key }}
										</td>
										
										<td style="width: 20%;word-wrap: break-all;">
											{{ $schedule->title }}
										</td>
										<td style="width: 20%">
											{{ $schedule->present()->jobRepLaborSubAll }}
										</td>										
										<td style="width: 10%">
											{{ $startDate->format(config('jp.date_format'))  }}
										<td style="width: 10%">
											{{ $endDate->format(config('jp.date_format'))}}
										</td>
										<?php $days = $schedule->present()->manageOffDays ?>
										<td style="width: 10%">
											@if(ine($days, 'working_days'))
												{{ $days['working_days'] }}
											@else
												1
											@endif
											 Day(s)
										</td>

										<td style="width: 10%">
											{{ $schedule->present()->recurringText }}
										</td>
									</tr>
								</tbody>
							</table>
						</td>
					</tr>
					@endforeach
				</tbody>
			</table>
			@if(!count($schedules))
			<div class="no-record">No Records Found</div>
			@endif
		</div>
	</div>
</body>
</html>