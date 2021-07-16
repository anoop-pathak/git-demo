<!DOCTYPE html>
<html class="no-js" ng-app="jobProgress" ng-controller="AppCtrl">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title ng-bind="pageTitle"> JobProgress </title>
	<meta name="viewport" content="width=device-width">
	<link rel="stylesheet" href="{{config('app.url')}}css/main.css" />
	<style type="text/css">
		body {
			background: #fff;
			margin: 0;
			color: #333;
			font-size: 18px;
			/*font-family: Helvetica,Arial,sans-serif*/
		}
		label {
			color: #333;
		}
		p {
			margin: 0;
		}
		h1,h2,h3,h4,h5,h6 {
			margin: 0;
		}
		.container {
			width: 788px;
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
			vertical-align: top;
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
			margin-bottom: 10px;
		}
		.company-name {
			font-size: 20px;
			margin-bottom: 0;
			margin-top: 15px;
			/*color: #434343;*/
		}
		.filters-section h5 {
			font-weight: bold;
			font-size: 18px;
			margin-bottom: 5px;
		}
		.filters-section label {
			color: #666;
			font-size: 18px;
			font-weight: normal;
		}
		.filters-section label span {
			color: #000;
		}
		.filters-section label span.trade {
			text-transform: uppercase;
		}
		.jobs-list {
			border: 1px solid #ccc;
			margin: 20px 0;
		}
		.jobs-row {
			font-size: 0;
		}
		.jobs-row p {
			margin-bottom: 10px;
			font-size: 18px;
		}
		.job-col h3 {
			font-size: 18px;
			font-weight: normal;
			margin-bottom: 10px;
		}
		.job-col .rep {
			margin-top: 10px;
		}
		.job-col {
			border-right: 1px solid #ccc;
			display: inline-block;
			font-size: 18px;
			margin: 18px 0;
			padding: 0 18px;
			vertical-align: top;
			width: 45%;
		}
		.job-col:last-child {
			border-color: transparent;
		}
		.job-detail-part label {
			width: 145px;
			float: left;
			color: #333;
		}
		.job-detail-part p {
			display: block;
			margin-left: 147px;
			color: #555;
		}
		.upper-text {
			text-transform: uppercase;
		}
		.desc {
			margin: 0 15px;
			padding: 15px 0;
			border-top: 1px solid #ccc;
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
		.desc p {
			font-size: 18px;
			color: #555;
			display: inline;
		}
		/*.jobs-list:nth-child(2n+2) {
			background: rgba(0,0,0,0.02);
			}*/
			.separator {
				border: 1px solid #dfdfdf;
			}
			.attendees-label{
				float: left;
			}
			.job-detail-part p.attendees-list{
				display: block;
				vertical-align: top;
				margin-left: 110px;
			}
			.job-detail-part p.attendees-list span{
				white-space: nowrap;
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
			.jobs-label{
				float: left;
				width: 110px;
			}
			p.job-number{
				display: block;
				/*padding-left: 110px;*/
			}
			.job-detail-part span{
				/*white-space: nowrap;*/
			}
			.today-date p{
				text-align: right;
				padding-bottom: 3px;
			}
			.description {
				text-align: justify;
				white-space: pre-wrap;
			}
			.job-des {
				display: inline-block;
				width: 100%;
				white-space: nowrap;
				overflow: hidden;
				text-overflow: ellipsis;
			}
			.job-detail-part .attendees-list.appointment {
				display: block;
				margin-left: 147px;
			}
			.job-detail-part .job-number.job{
				margin-left: 145px;
				display: block;
				padding-left: 0;
			}
			p, span {
				font-weight: normal;
			}
			.job-heading {
				/*color: #434343;*/
				color: #333;
			}
			.text-alignment {
				display: inline;
			}
			.address-info-sec {
				margin-bottom: 10px;
				display: block;
				color: #555;
			}
			.email-field {
				word-break: break-all;
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
			.complete-label {
				background: green;
				display: inline-block;
				color: #fff;
				padding: 2px 8px;
				border-radius: 3px;
				font-size: 12px;
			}
			.text-right {
				text-align: right;
			}
			.job-heading {
				/*display: block;*/
			}
			.mb10 {
				margin-bottom: 10px;
			}
			.app-result .job-heading {
				color: #000;
			}
			.app-result .app-label {
			    background: #888;
			    color: #fff;
			    font-size: 12px;
			    line-height: normal;
			    vertical-align: middle;
			    padding: 2px 5px;
			    border-radius: 3px;
			}
			.job-numbers {
				display: inline-block;
				width: 195px;
				white-space: nowrap;
				overflow: hidden;
				text-overflow: ellipsis;
			}
		</style>
	</head>
	<body>
		<div class="container">
			<div class="jobs-export">
				<div class="header-part">
					@if(! empty($company->logo) )
					<div class="company-logo">
						<img src="{{ FlySystem::getUrl(config('jp.BASE_PATH').$company->logo) }}" />
					</div>
					@endif
					<h1 style="width:70%;">{{ $company->name ?? ''}}</h1>

					<p class="company-name">Appointment</p>
				</div>
				<div class="main-logo">
					<img src="{{asset('main-logo.png')}}">
					 <div class="today-date">
	                    <p><label>Current Date: </label><?php echo  \Carbon\Carbon::now(Settings::get('TIME_ZONE'))->format(config('jp.date_format')) ; ?></p>
						@if($appointment->completed_at)
							<div class="text-right">
		                    	<span class="complete-label">Completed</span>
		                    </div>
						@endif
	                </div>
				</div>
				<div class="clearfix"></div>
				<!-- <div class="filters-section">
					<span class="label label-default">Default</span>
				</div> -->
				<div>
				
					<div class="jobs-list">
						<div class="jobs-row">
							<div class="job-col job-detail-part">
								<h3 class="job-heading">{{$appointment->title ?? ''}}</h3>
								<span class="text-alignment address-info-sec">{{$appointment->location ?? ''}}</span>
								<div><label>Recurring:</label><p>
									{{ $appointment->present()->recurringText }}
								</p></div>
								<div><label>Appointment For: </label><p>
									<span class="text-alignment">{{ $appointment->present()->assignedUserName }}</span>
							</p></div>
								@if($appointment->createdBy)
									<div><label class="attendees-label">Created By: </label>
									<p class="attendees-list appointment">
										{{$appointment->createdBy->full_name}}
									</p></div>
								@endif
								@if( sizeof($appointment->attendees) > 0)
									<div><label class="attendees-label">Attendees: </label>
									<p class="attendees-list appointment">
										{{ implode(', ', $appointment->attendees->pluck('full_name')->toArray()) }}
									</p></div>
								@endif
								<div><label>Start Time: </label><p>
									<?php 
										$dateTime = new Carbon\Carbon($appointment->start_date_time,'UTC');
										$dateTime->setTimeZone(Settings::get('TIME_ZONE'));
									?>
									<span style="display: inline; white-space: nowrap;">
									{{ $dateTime->format(config('jp.date_time_format')) }}
									</span>
								</p></div>
								<div><label>End Time: </label><p class="text-alignment"> 
									<?php 
										$dateTime = new Carbon\Carbon($appointment->end_date_time,'UTC');
										$dateTime->setTimeZone(Settings::get('TIME_ZONE'));
									?>
									<span style="display:inline; white-space: nowrap;">
									{{ $dateTime->format(config('jp.date_time_format')) }}
									</span>
								</p></div>
							</div>
							<div class="job-col job-detail-part">
								@if($customer = $appointment->customer)
									@if($customer->is_commercial)
										@if($secName =  $customer->present()->secondaryFullName)
										<div>
											<label>Customer Name: </label>
											<p>{{$secName}}</p>
										</div>
										@endif
										<div>
											<label>Company Name: </label>
											<p>{{$customer->first_name}}</p>
										</div>
									@else
										<div>
											<label>Customer Name: </label>
											<p>{{$customer->full_name}}</p>
										</div>
										@if($customer->company_name)
										<div>
											<label>Company Name: </label>
											<p>{{$customer->company_name}}</p>
										</div>
										@endif
									@endif
									@if($customer->email)
										<div><label>Email: </label><p class="email-field">
										{{$customer->email}}
										</p></div>
										@endif
								@endif
								@if(count($appointment->jobs))
									<?php
									$jobNumbers = [];
									$workTypes  = [];
									?>
									@foreach($appointment->jobs as $job)
										<?php
											if(count($job->workTypes)) {
												$workTypeList = $job->workTypes->pluck('name')->toArray();
												$workTypes[] = implode(', ', $workTypeList);
											}
										 ?>
									@endforeach
									<div><label class="jobs-label">Jobs: </label><p class="job-number">
										<div class="job-numbers">
										{!! $appointment->present()->jobDetails !!}
										</div>
									</p></div>
									@if(!empty($workTypes))

									<div><label class="jobs-label">Work Types: </label><p class="job-number">
										{{ implode(', ', $workTypes) }}
									</p></div>
									@endif
								@endif
								@if($customer = $appointment->customer)
									@foreach( $customer->phones as $phone )
									<div><label>{{ ucfirst($phone->label) }}: </label>
									 <p>{{ phoneNumberFormat($phone->number, $company_country_code) }}
										@if($phone->ext)
										{!! '<br>EXT: '. $phone->ext !!}
										@endif
									</p>
									</div>
									@endforeach
								@endif
							</div>
						</div>
						<!-- appointment result section -->
						<div class="desc">
							{!! $appointment->present()->appointmentResult() !!}
						</div>

						@if($appointment->description)
						<div class="desc">
							<p>
								<span class="job-heading">Appointment Note:</span>
								<span class="description">{{$appointment->description}}</span>
							</p>
						</div>
						@endif

						@if(($descriptions = $appointment->present()->jobsDescription))
							<div class="desc">
								<div class="job-des">
									<p>
										<span class="job-heading">Job Description:</span>
										<span>{!! $descriptions !!}</span>
									</p>
								</div>
						</div>
						@endif
					</div>
				</div>
			</div>
		</div>

	</body></html>