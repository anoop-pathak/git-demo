<!doctype html>
<html class="no-js"  ng-app="jobProgress" ng-controller="AppCtrl">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title ng-bind="pageTitle"> JobProgress </title>
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
	<link rel="stylesheet" href="{{config('app.url')}}css/main.css" />
	<meta name="viewport" content="width=device-width">
	<style type="text/css">
		body {
			background: #fff;
			margin: 0;
			font-size: 18px;
			font-family: Helvetica,Arial,sans-serif;
			color: #333;
		}
		body label {
			color:#333;
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
			/*width: 788px;*/
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
		.header-part .job-col {
			padding: 0;
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
		.jobs-list {
			border: 1px solid #ccc;
			margin: 15px 0 30px;
		}
		.jobs-row {
			font-size: 0;
			display: flex;
			-webkit-display: flex;
		}
		.jobs-row p {
			margin-bottom: 10px;
			font-size: 18px;
		}
		.job-col h3 {
			font-size: 20px;
			font-weight: normal;
			margin-bottom: 5px;
			white-space: nowrap;
		}
		.job-col .rep {
			margin-top: 10px;
		}
		.job-col {
			border-left: 1px solid #ccc;
			display: inline-block;
			flex: 1 1 0;
			font-size: 18px;
			margin-top: 12px;
			padding: 0 18px;
			vertical-align: top;
			width: 45%;
		}
		.job-col:first-child {
			border-color: transparent;
		}
		.upper-text {
			text-transform: uppercase;
			/*font-size: 18px;*/
		}
		.desc {
			padding: 15px 0px;
			border-top: 1px solid #ccc;
			margin: 0 15px;
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
		p.desc {
			margin-bottom: 0;
		}
		.desc .job-desc {
			font-size: 18px;
		}
		.desc .job-desc strong {
			font-size: 18px;
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
		.upcoming-appointment-title {
			display: inline-block;
			margin-bottom: 5px;
			width: 68%;
		}
		.job-detail-appointment {
			border-bottom: 1px solid #ccc;
			cursor: pointer;
			padding: 10px 0;
			font-size: 18px;
		}
		.job-detail-appointment:last-child {
			border-bottom: none;
		}
		.job-address {
			/*float: left;*/
		}
		.job-address i {
			font-style: normal;
		}
		.job-address label {
			/*width: 160px;*/
			/*white-space: nowrap;*/
		}
		.job-address label, .phone-list label {
			float: left;
			width: 145px;
			margin-bottom: 10px;
		}
		.job-address .referred-by {
			margin-left: 110px;
			vertical-align: top;
		}
		.job-address .sales-man {
			width: 135px;
			white-space: normal;	
		}
		.job-address span, .phone-list span {
			display: block;
			white-space: normal;
			margin-left: 148px;
		}
		.customer-name {
			margin-bottom: 8px;
		}
		.customer-name h3 {
			margin-bottom: 0;
		}
		.customer-name .btn-flags{
			font-size: 11px;
			padding: 1px 6px;

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
		.legend > span{
			float: right;
			font-size: 18px;
		}
		.today-date {
			margin-top: 10px;
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
		.appointment-meta{
			margin-bottom: 5px;
		}
		.appointment-meta .assign_to {
			margin: 3px 0;
		}
		.job-status-container{margin-bottom:10px;font-size:0;}
		.job-status-container .stage-col{ display: inline-block;text-align: center;vertical-align: top;}
		.job-status-container .stage-col .stage-name-col { height: 15px;width: 12px; }
		.job-status-container .stage-col .stage-name-col::after { border-width: 8px 7px 0;bottom: -8px; }
		.job-status-container .stage-col .stage-name-col::before { border-width: 10px 10px 0;bottom: -10px; }
		.job-status-container .stage-col .stage-progress-line .stage-dot { height: 10px;width: 10px;}
		.job-status-container .stage-col .stage-progress-line {border-bottom: 2px solid #ddd;}
		.phone-list span.ext{
			display: block;
		}
		.btn-flags {
			border: none;
			color: #fff;
			background: #777;
			font-size: 14px;
			cursor: default;
			height: 16px;
			width: auto;
			line-height: 16px;
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
		.description {  
			text-align: justify;
			white-space: pre-wrap;
		}
		.activity-img{
			width: auto; 
			min-width: 24px; 
			top: 50%;
			height: 24px;
			/*line-height: 24px;*/
			float: left;
			border-radius: 50%;
			margin-right: 5px;
			background: #ccc;
		}
		td .appointment-sr-container{
			padding: 10px 0px;  
		}
		.activity-img p{
			font-size: 18px;
			color: #333;
		}
		.appointment-sr-container .activity-img p {
			padding: 3px 0;
			font-size: 18px;
			color: #333;
			text-align: center;
		}
/*		.appointment-desc label, .location-box label {
			font-weight: bold;
		}
		.assign_to {
			font-weight: bold;
		}*/
		.btn-flags.label-warning {
			background: #f0ad4e;
		}
		.project-job-row {
			border-top: 1px solid #ccc;
			padding: 15px 0 10px;
			margin-top: 15px;
		}
		.project-job-row .job-address {
			margin-bottom: 0;
		}
		.project-job-list .project-container:first-child {
			margin-top: 0;
		}
		.project-container {
			border: 1px solid #ccc;
		}
		.project-container {
			margin: 20px 0;
		}
		.customer-name-overview {
			display: inline-block;
		}
		p, span {
			font-weight: normal;

		}
		h3{
			font-size: 20px;
			font-weight: normal;
			/*color: #434343;*/
		}
		.job-heading {
			/*color: #434343;*/
		}
		.appointment-container {
			margin: 0 15px;
		}
		.appointment-sr-container {
			margin-top: 13px;
			margin-left: 10px;
			margin-right: -14px;
		}
		.text-alignment {
			display: inline;
		}
		.appointment-badge-wrap {
			padding: 0 !important;
			margin-right: 0;
			margin-left: 0;
		}
		.appointment-label {
			float: left; 
			width: 101px;
		}
		.appointment-span-desc {
			display: block; 
			margin-left: 101px;
		}
		.job-address span.sales-man-wrapper {
			margin-left: 135px;
			display: block;
		}
		.work-crew-listing.cust-job-detail .details-col:hover {
			background-color: transparent;
		}
		.work-crew-listing.cust-job-detail .details-col {
			padding: 0;
		}
		.work-crew-listing.cust-job-detail .work-crew-row {
			border-bottom: 1px solid #ccc; 
			padding: 10px; 
			margin: 0;
		}
		.work-crew-listing.cust-job-detail .work-crew-row:first-child {
			padding-top: 0;
		} 
		.work-crew-listing.cust-job-detail .work-crew-row:last-child {
			border-bottom: 0;
			padding-bottom: 0;
		}
		.work-crew-listing.cust-job-detail .work-crew-row p {
			margin: 5px 0;
			font-size: 16px;
		}
		.work-crew-listing.cust-job-detail .work-crew-row h4 {
			margin-top: 0;
			font-size: 16px;
		}
		.work-crew-listing.cust-job-detail .work-crew-row .fa-exclamation-triangle {
			color: #f0ad4e;
		}
		.work-crew-listing.cust-job-detail .details-col .job-rep-detail {
			display: inline-block;
			border-radius: 10px;
			padding: 2px 3px;
			font-size: 12px;
			vertical-align: middle;
			margin: 2px 0;
		}
		.job-id-replace {
			display: inline-block;
			width: auto;
			max-width: 626px;
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
		}
	</style>
</head>

<body>
	<div class="container">
		<div class="jobs-export">
			<div class="main-logo">
				<img src="{{asset('main-logo.png')}}">
				<div class="today-date">
					<p><label>Current Date: </label><?php echo  \Carbon\Carbon::now(\Settings::get('TIME_ZONE'))->format(config('jp.date_format'));?></p>
				</div>
			</div>
			<div class="header-part">
				@if(! empty($company->logo) )
				<div class="company-logo">
					<img src="{{ FlySystem::getUrl(config('jp.BASE_PATH').$company->logo) }}" />
				</div>
				@endif
				<h1 style="width:70%;">{{ $company->name ?? ''}}</h1>
			</div>
			<div class="clearfix"></div>
			<div class="jobs-row">
				<div class="job-col customer-name" style="display: inline;padding: 0;">
					<h3 class="customer-name-overview" style="margin-bottom: 10px;white-space: normal;" >{{ $job->customer->first_name or ''}} {{ $job->customer->last_name or ''}} / </label><span class="job-id-replace">{{ $job->present()->jobIdReplace}}</span></p></h3>
					@if($job->isMultiJob())
					<span class="btn-flags label label-warning" style="top: -5px; display: inline; vertical-align: middle;">Multi Project</span>
					@endif
				</div>
			</div>

			<div class="main-logo">
				<div class="today-date">
					<p><label>Current Date: </label><?php echo  \Carbon\Carbon::now(Settings::get('TIME_ZONE'))->format(config('jp.date_format')) ; ?></p>
				</div>
			</div>
			<div class="clearfix"></div>

			<div class="customer-info">
				<h3 class="upper-text">Customer Information</h3>
				<div class="jobs-list">
					<div class="jobs-row">
						<div class="job-col">
							@if($job->customer->company_name)
							<p>{{ $job->customer->company_name ?? ''}}</p>
							@endif
							@if($secName = $job->customer->present()->secondaryFullName)
							<p>{{ $secName }}</p>
							@endif
							@if($job->customer->email)
							<p>{{ $job->customer->email ?? ''}}</p>
							@endif
							<p class="job-address">
								<label class="sales-man">Salesman / Customer Rep:</label>
								<span class="sales-man-wrapper">
									@if(isset($job->customer->rep->first_name) || isset($job->customer->rep->last_name))
									{{ $job->customer->rep->first_name ?? '' }} {{ $job->customer->rep->last_name ?? '' }}
									@else
									Unassigned
									@endif
								</span>
							</p>
							<div class="clearfix"></div>

							@if(!Auth::user()->isSubContractorPrime())
								<?php $referredBy = $job->customer->referredBy();?>
								@if($job->customer->referred_by_type == 'customer')
								<p class="job-address"><label style="width:135px;">Referred By: </label><span class="referred-by" style=" text-align: justify;margin-left: 135px;">{{ $referredBy->first_name ?? ''}} {{ $referredBy->last_name ?? ''}}
									<i style="font-size: 13px;font-style: normal;"><br>(Existing Customer)</i></span>
								</p>
								@elseif($job->customer->referred_by_type == 'other')
								<p class="job-address"><label style="width:135px;">Referred By: </label><span class="referred-by" style=" text-align: justify;margin-left: 135px;">{{ $job->customer->referred_by_note ?? ''}}</span></p>
								@elseif($job->customer->referred_by_type == 'referral')
								<p class="job-address"><label style="width:135px;">Referred By: </label><span class="referred-by" style=" text-align: justify;margin-left: 135px;">{{ $referredBy->name ?? ''}}</span></p>
								@endif
							@endif
						</div>
						<div class="job-col job-detail-part">
							@foreach($job->customer->phones as $phone)	
							<p class="phone-list">
								<label>{{ ucfirst($phone['label']) }}: </label>
								<span>
									{{ phoneNumberFormat($phone['number'], $company_country_code) }}
								</span>
								@if($phone['ext'])
								<span class="ext">{{ 'EXT: '. $phone['ext'] }}</span>
								@endif	
							</p>
							@endforeach
							<?php $customerAddress = null; ?>
							@if(($job->customer->address)  
							&& ($customerAddress = $job->customer->address->present()->fullAddress))
							<p class="job-address">
								<label>Address: </label>
								<span>
									{!! $customerAddress !!}
								</span>
							</p>
							@endif
							@if(($job->customer->billing) 
							&& ($billingAddress = $job->customer->billing->present()->fullAddress))
							<p class="job-address">
								<label>Billing Address: </label>
								<span>
									{!! $billingAddress !!}
								</span>
							</p>
							@endif								
						</div>
					</div>
					@if(sizeOf($job->customer->flags))
					<div class="desc bd-t0"> 
						<p class="flag">
							@foreach($job->customer->flags as $flag)
							<span class="btn-flags label label-default" style="background-color: {{ $flag->color_for_print }}">{{ $flag->title }}</span>
							@endforeach
						</p>
					</div>
					@endif
					@if(!empty($job->customer->note))
					<div class="desc"> 
						<p class="job-desc">
							<label class="job-heading">Customer Note: </label>
							<span class="description">{!! $job->customer->note ?? '' !!}</span>
						</p>
					</div>
					@endif
				</div>
				<!-- <div class="separator"></div> -->
			</div>
			<div>
				<h3 class="upper-text">Job Overview</h3>
				<div class="jobs-list">
					<div class="jobs-row">
						<div class="job-col">
							<p class="job-address">
								<label class="text-alignment">Job ID: </label>
								<span>{{ $job->number ?? ''}}</span>
							</p>
							<div class="clearfix"></div>
							@if($job->alt_id)
							<p class="job-address">
								<label class="text-alignment">Job #: </label>
								<span style="word-break: break-all;">
									{{ $job->full_alt_id }}
								</span>
							</p>
							@endif

							@if(isset($job->address) 
							&& ($jobAddress = $job->address->present()->fullAddress))
							<p class="job-address">
								<label class="text-alignment">Job Address: </label>
								<span>
									<?php echo $jobAddress; ?>
								</span>
							</p>
							@endif
							@if($job->duration_in_seconds)	
							<p class="job-address">
								<label class="text-alignment">Job Duration: </label>
								<span>
									{!! $job->present()->jobDuration !!}
								</span>
							</p>		
							@endif
							<div class="clearfix"></div>
							@if(count($job->jobTypes))
							<?php $jobTypes = []; ?>
							@foreach($job->jobTypes as $jobType)
							<?php $jobTypes[] = $jobType->name; ?>
							@endforeach
							<p class="job-address">
								<label>Category: </label>
								<span class="upper-text">
									{{ implode(', ', $jobTypes) }}
								</span>
							</p>
							@endif
							@if($job->jobWorkflow->stage)
							<p class="job-address">
								<label>Stage: </label>
								<span><i class="jp-stage-color {{ $job->jobWorkflow->stage->color }}">{{ $job->jobWorkflow->stage->name }}</i><br><span style="white-space: nowrap;margin-left:0; ">(Since: {{ date(config('jp.date_format'),strtotime($job->jobWorkflow->stage_last_modified)) }})</span>
								</span>
							</p>
							@endif
							@if(! $job->isMultiJob())
							<p class="job-address">
								<label>Job Rep / Estimator: </label>
								<span>{{ $job->present()->jobEstimators }}</span>
							</p>
							@endif
						</div>
						<div class="job-col job-detail-part">
							<div>
								<div class="job-status-container">
									<?php
									$stages = $job->workflow->stages; 
									$stages_count = $stages->count();
									$width = 100/$stages_count;
									?>
									@foreach($stages as $key => $stage)
									@if(isset($completed_stages[$stage->code]))
									<?php 
									$status = 'completed';
									$color  = $stage->color;
									?>
									@elseif(isset($job->jobWorkflow->stage->code) && ($stage->code == $job->jobWorkflow->stage->code))
									<?php
									$status = 'active';	
									$color  = $stage->color;
									?>
									@else 
									<?php
									$status = null;
									$color  = null;
									?>
									@endif
									<div class="stage-col {{$color}} {{$status}}" style="width:{{$width}}%;">
										<div class="stage-box">
											<div class="stage-name-col">
												<p>{{substr($stage->name, 0,1)}}</p>
											</div>	
										</div>
										<div class="stage-progress-line">
											<div class="stage-dot"></div>
										</div>
									</div>
									@endforeach
								</div>
							</div>

							@if(count($job->trades))
							<p class="job-address">
								<label>Trade Type: </label>
								<span>
									{{ implode(', ',$job->trades->pluck('name')->toArray()) }}
								</span>
							</p>
							@endif
							<div class="clearfix"></div>
							@if(count($job->workTypes) && (! $job->isMultiJob()))
							<?php $workTypes = []; ?>
							@foreach($job->workTypes as $workType)
							<?php $workTypes[] = $workType->name ?>
							@endforeach
							<p class="job-address"><label>Work Types: </label><span>{{ implode(', ', $workTypes) }} </span></p>
							@endif
							<p class="job-address">
								<label>Job Record Since: </label>
								<span>
									<?php 
									$dateTime = new Carbon\Carbon($job->created_date,'UTC');
									$dateTime->setTimeZone(Settings::get('TIME_ZONE'));
									?>
									{{ $dateTime->format(config('jp.date_format')) }}
								</span>
							</p>
							<div class="clearfix"></div>
							<p class="job-address">
								<label>Last Modified: </label>
								<span>
									<?php 
									$dateTime = new Carbon\Carbon($job->updated_at,'UTC');
									$dateTime->setTimeZone(Settings::get('TIME_ZONE'));
									?>
									{{ $dateTime->format(config('jp.date_format')) }}
								</span>
							</p>
						</div>
					</div>
					<?php $status = $job->getScheduleStatus(); ?>
					@if(!empty($job->call_required) 
					|| !empty($job->appointment_required) 
					|| sizeOf($job->flags)
					|| $job->isMultiJob()
					|| ($status)
					)
					<div class="desc bd-t0 flag-container"> 
						<p class="flag"> 
							@if($status)
								<span class="btn-flags label label-default">{{ $status }}</span>
							@endif
							@if($job->call_required)
							<span class="btn-flags label label-default">Call Required</span>
							@endif
							@if($job->appointment_required)
							<span class="btn-flags label label-default">Appointment Required</span>
							@endif
							@if(sizeOf($job->flags))
							@foreach($job->flags as $flag)
							<span class="btn-flags label label-default" style="background-color: {{ $flag->color_for_print }}">{{ $flag->title }}</span>
							@endforeach

							@endif
						</p>
					</div>
					@endif

					@if(!empty($job->description) || ($job->isMultiJob() && $job->projects->count()) )

					<?php $descStyle = null; ?>

					@if($job->isMultiJob())
					<?php $descStyle = "border-top:none; padding-top:0;" ?>
					@endif

					<div class="desc" style="<?php echo $descStyle ?>">
						<?php $projectStyle = "margin-top:0;" ?>
						@if($job->description)
						<p class="job-desc">
							<?php $projectStyle = null; ?>
							<label class="job-heading">Job Description: </label>
							<span class="description">{!! lineBreak($job->description) !!}</span>
						</p>
						@endif
						@if($job->isMultiJob() && $job->projects->count())
						<div class="jobs-row project-job-row" style="<?php echo $projectStyle; ?>">
							<div>
								<h3>
									@if($is_project)
									Project
									@else
									Projects
									@endif
									({{$job->projects->count()}})
								</h3>
							</div>
						</div>
						<div class="project-job-list">
							@foreach($job->projects as $key => $project)
							<div class="project-container" style="border-bottom: 1px solid #ccc;">
								<div class="appointment-sr-container" style="float: left;">
									<div class="activity-img notification-badge">
										<div>
											<span>
												<p class="">{{ $key+1 }}</p>
											</span>
										</div>
									</div> 
								</div>
								<div class="jobs-row">
									<div class="job-col" style="width: 42.2%">
										<p class="job-address"><label class="text-alignment">Project ID: </label><span style="white-space: nowrap;">{{ $project->number }}</span></p>
										@if($project->alt_id)
										<p class="job-address">
											<label class="text-alignment">Project #:</label><span style="word-break: break-all;">{{ $project->full_alt_id }}</span>
										</p>
										@endif
										@if($project->duration_in_seconds)	
										<p class="job-address">
											<label>Project Duration: </label>
											<span>{!! $project->present()->jobDuration !!}</span>
										</p>
										@endif
										<div class="clearfix"></div>
										@if(count($project->jobTypes))
										<?php $jobTypes = []; ?>
										@foreach($project->jobTypes as $jobType)
										<?php $jobTypes[] = $jobType->name; ?>
										@endforeach
										<p class="job-address">
											<label>Category: </label>
											<span class="upper-text">{{ implode(', ', $jobTypes) }}</span>
										</p>
										@endif
										<p class="job-address">
											<label class="text-alignment">Project Rep / Estimator: </label>
											<span>{{ $project->present()->jobEstimators }}</span>
										</p>
									</div>
									<div class="job-col job-detail-part" style="width: 45%">
										@if($project->projectStatus)
										<p class="job-address">
											<label>Project Status: </label>
											<span>
												{{ $project->projectStatus->name ?? '' }}
											</span>
										</p>	
										@endif
										@if(count($project->jobTypes))
										<p class="job-address">
											<label>Category: </label><p class="upper-text">
											<?php $jobTypes = []; ?>
											@foreach ($project->jobTypes as $jobType)
											<?php $jobTypes[] = $jobType->name ?>
											@endforeach
											<span>
												{{implode(', ', $jobTypes)}}
											</span>
										</p>
										@endif
										<p class="job-address">
											<label>Trade Type: </label>
											<span>
												{{ implode(', ', $project->trades->pluck('name')->toArray()) }}
											</span>
										</p>
										<div class="clearfix"></div>
										@if(count($project->workTypes))
										<?php $workTypes = []; ?>
										@foreach($project->workTypes as $workType)
										<?php $workTypes[] = $workType->name ?>
										@endforeach
										<p class="job-address"><label>Work Type: </label><span>{{ implode(', ', $workTypes) }} </span></p>
										@endif

										<p class="job-address">
											<label>Project Record Since: </label>
											<span>
												<?php 
												$dateTime = convertTimezone($project->created_date, Settings::get('TIME_ZONE'));
												?>
												{{ $dateTime->format(config('jp.date_format')) }}
											</span>
										</p>
										<div class="clearfix"></div>
										<p class="job-address">
											<label>Last Modified: </label>
											<?php $updatedTime = convertTimezone($project->updated_at, Settings::get('TIME_ZONE')); ?>
											<span>
												{{ $updatedTime->format(config('jp.date_format')) }}
											</span>
										</p>
									</div>
								</div>
							@if($status = $project->getScheduleStatus())
								<div class="desc bd-t0 flag-container">
									<p class="flag">
										<span class="btn-flags label label-default">
											{{ $status }}
										</span>
									</p>
								</div>
							@endif	
							@if($project->description)
							<div class="desc" style=""> 
								<p class="job-desc">
									<label class="job-heading">Project Description: </label>
									<span class="description">{{ lineBreak($project->description) }}</span>
								</p>
							</div>
							@endif
						</div>
						@endforeach
					</div>
					@endif
					<?php 
					$todayDate = \Carbon\Carbon::now(Settings::get('TIME_ZONE'))->toDateString();  
					$appointments = $job->appointments()->DateRange($todayDate)->get();
					?>
					@if(sizeOf($appointments))
					<div class="desc appointment-container">
						<h3>Appointments </h3>
						<table>
							@foreach($appointments as $key => $appointment)
							<tr>
								<td valign="top">
									<div class="appointment-sr-container appointment-badge-wrap">
										<div class="activity-img notification-badge">
											<div>
												<span>
													<p class="">{{ $key+1 }}</p>
												</span>
											</div>
										</div> 
									</div>
								</td>
								<td width="100%">
									<div class="job-detail-appointment">
										<div class="cust-address">  
											<div>
												<p class="upcoming-appointment-title">
													<span>{{ $appointment->title ?? ''}}</span>
												</p>
												<div class="pull-right">
													<span style="white-space: nowrap;">
														<?php 
														$dateTime = new Carbon\Carbon($appointment->start_date_time,'UTC');
														$dateTime->setTimeZone(Settings::get('TIME_ZONE'));
														?>
														{{ $dateTime->format(config('jp.date_time_format')) }}
													</span>
												</div>
											</div>
											<div>
												<div class="appointment-desc">
													<label class="appointment-label">Recurring: </label>
													<span class="description appointment-span-desc">{{ $appointment->present()->recurringText}}</span>
												</div>
											</div>
											<div class="appointment-meta">
												<div class="location-box">
													<label class="appointment-label">Location: </label>
													<span class="appointment-span-desc">{{ $appointment->location ?? '' }}</span>
												</div>
												<div class="assign_to"> 
													<label class="appointment-label">Assign To:</label>
													<span class="appointment-span-desc">{{ $appointment->present()->assignedUserName }}</span>
												</div>
											</div>
											@if($appointment->createdBy)
											<div>
												<div class="appointment-desc">
													<label class="appointment-label">Created By: </label>
													<span class="description appointment-span-desc">{{ $appointment->createdBy->full_name}}</span>
												</div>
											</div>
											@endif
											@if($appointment->description)
											<div>
												<div class="appointment-desc">
													<label class="appointment-label">Note: </label>
													<span class="description appointment-span-desc">{{ $appointment->description}}</span>
												</div>
											</div>
											@endif
										</div>
									</div>
								</td>
							</tr>
							@endforeach
						</table>
					</div>
					@endif
				</div>

			</div>
			
		</div>
		@endif
	</div>
</body>
</html>