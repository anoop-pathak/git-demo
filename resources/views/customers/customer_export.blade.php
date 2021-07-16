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
			font-family: Helvetica,Arial,sans-serif;
			color: #333;
			font-size: 18px;
		}
		body label{
			color: #333;
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
			width: 100%;
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
			/*font-we*/ight: bold;
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
			font-size: 20px;
			font-weight: normal;
			margin-bottom: 5px;
		}
		.job-col .rep {
			margin-top: 10px;
			clear: both;
		}
		.rep label {
		/*float: left;
		width: 180px;*/
	}
	.rep p {
		display: block;
		white-space: normal;
		margin-left: 180px;
	}
	.job-col {
		border-right: 1px solid #ccc;
		display: inline-block;
		font-size: 18px;
		margin-top: 12px;
		padding: 0 18px 0 28px;
		vertical-align: top;
		width: 44.3%;
		position: relative;
	}
	.job-col .job-address label {
		float: left;
		width: 90px;
		margin-bottom: 10px;
		/*font-weight: bold;*/
	}
	.job-col .job-address p {
		display: block;
		margin-left: 90px;
	}
	.job-col .job-address i {
		font-style: normal;
	}
	.job-col:last-child {
		border-color: transparent;
	}
	.job-detail-part label {
		/*width: 30%;*/
		/*display: inline-block;
		vertical-align: top;*/
	}
	.job-detail-part p {
		/*display: inline-block;
		vertical-align: top;*/
	}
	.upper-text {
		text-transform: uppercase;
	}
	/*.desc {
		margin: 0 15px;
		padding: 15px 0;
		border-top: 1px solid #ccc;
	}*/
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
	.desc {
		border-top: 1px solid #ccc;
		margin: 0 15px;
		padding: 15px 14px;
		font-size: 18px;
	}
	.desc p strong {
		font-size: 18px;
	}
	.customer-desc{
		/*margin-left: 0; */
	}
	.custom-desc{
		margin-right: 15px;
		padding: 5px 13px;
		font-size: 18px;
		border-top: none;
	}
	/*.jobs-list:nth-child(2n+2) {
		background: rgba(0,0,0,0.02);
	}*/
	.separator {
		border: 1px solid #dfdfdf;
	}
	.pull-right {
		float: right;
	}
	.stage i {
		font-style: normal;
	}
	.job-address span{
		display: block;
		margin-left: 110px;
		white-space: normal;
	}
	.job-full-col {
		width: 100%;
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
	.job-col .job-address p{
		margin-left: 126px;
	}
	.job-col .job-address label{
		width: 126px;
	}
	.legend > span{
		float: right;
		font-size: 18px;
	}
	.today-date p{
		text-align: right;
		padding-bottom: 3px;
		/*font-weight: bold;*/
	}
	.today-date p label {
		color: #333;
	}
	/*.customer-container{
		display: inline;
	}*/
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
	.text-right{
		float: right;
	}
	.today-date p{
		text-align: right;
		padding-bottom: 3px;
	}
	.location-box{
		display: inline-block;
	}
	p.flag{
		display: inline-block;
		vertical-align: top;
		font-size: 18px;
		margin-bottom: 0px;
	}
	p.flag span{
		margin-bottom: 5px;
		display: inline-block;
	}
	.appointment-meta{
		margin-bottom : 5px;
	}
	p.job-flag{
		/*margin-left: 22px;*/
	}
	td .appointment-sr-container{
		padding: 10px 0px;  
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
  .description {	
  	text-align: justify;
  	white-space: pre-wrap;
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
  }
  td .appointment-sr-container{
  	padding: 10px 0px;  
  }
  .activity-img p{
  	font-size: 18px;
  	color: #333;
  }
  .appointment-sr-container .activity-img p {
  	padding: 3px 5px;
  	font-size: 13px;
  	color: #333;
  	text-align: center;
  }
/*  .appointment-desc label, .location-box label {
  	font-weight: bold;
  }
  .assign_to {
  	font-weight: bold;
  }*/
  .btn-flags.label-warning {
  	background: #f0ad4e;
  }
    /*p.job-description {
    	padding-bottom: 14px;
    }*/
    .project-job-row {
    	border-top: 1px solid #ccc;
    	padding: 15px 0 10px;
    	margin-top: 15px;
    }
    .project-job-row .job-address {
    	margin-bottom: 0;
    }
    .project-job-list .jobs-list:first-child {
    	margin-top: 0;
    }
    .job-flag.multiproject-id-btn {
    	margin-top: -7px;
    	margin-bottom: 2px;
    }
    .multiproject-id-btn span {
    	margin: 0;
    }
    .multiproject-id-btn .btn-flags{
    	font-size: 11px;
    	padding: 1px 6px;
    }
    p, span {
    	font-weight: normal;
    }
    label {
	    color: #333;
	}

	.job-col h3 {
	    /*color: #434343;*/
	    font-size: 20px;
	    font-weight: normal;
	    margin-bottom: 5px;
	}
	.job-desc-lable {
		/*color: #434343;*/
	}
	.appointment-container h3, .job-heading {
		/*color: #434343;*/
	    font-size: 20px;
	    font-weight: normal!important;
	}
    .job-heading {
    	padding-top: 5px;
	 	padding-bottom: 0;
	 }
	 h4 {
	 	font-weight: normal;
	 }
	 .projects-badge .activity-img {
	 	min-width: 40px;
	 	height: 40px;
	 	line-height: 40px;
	 }
	 .projects-badge .activity-img p {
	 	padding: 0;
	 	font-size: 12px;
	 }
	 .referred-by-desc {
	 	display: inline-flex;
	    text-align: justify;
	    /*width: 200px;*/
	 }
	 .duration span {
	 	margin-left: 0;
	 }
	 .appointment-meta .assign_to {
		margin: 3px 0;
	}	
	.appointment-label {
	 	float: left; 
	 	width: 101px;
	 }
	.appointment-span-desc {
	 	display: block; 
	 	margin-left: 101px;
	}
	.salesman {
		display: inline-block;
		width: 130px;
	}
	span.salesman-name {
		vertical-align: top;
		margin-left: 130px;
	}
	.clearfix {
        clear: both;
     }
</style>
</head>
<body>
	<div class="container">
		<div class="jobs-export">
			<div class="header-part">
				@if( ! empty($company->logo) )
				<div class="company-logo">
					<img src="{{ FlySystem::getUrl(config('jp.BASE_PATH').$company->logo) }}" />
				</div>
				@endif
				<h1 style="width: 45%">{{$company->name ?? '' }}</h1>

				<div class="main-logo">
					<img src="{{asset('main-logo.png')}}">
					<div class="today-date">
						<p><label>Current Date: </label><?php echo  \Carbon\Carbon::now(Settings::get('TIME_ZONE'))->format(config('jp.date_format')) ; ?></p>
					</div>
					<!-- <div class="legend">
						<span>E Estimator</span>
					</div> -->
				</div>
			</div>
			<div class="jobs-list">
				<div class="jobs-row">
					<div class="job-col customer-container" >
						<h3>
							{{$customer->first_name}} {{$customer->last_name ?? ''}}
							<?php
							$todayAppointments = $customer->appointments()->today()->get();
							$upcomingAppointment  = $customer->appointments()->upcoming()->get()->count();
							?>
							@if(!empty($todayAppointments->count()) )
							<i class="fsz14 col-red fa fa-calendar"></i>
							@elseif ( empty($todayAppointments->count()) && !empty($upcomingAppointment) ) 
							<i class="fsz14 col-black fa fa-calendar"></i>
							@endif
						</h3> 
						
						@if(($customer->address) 
						&& ($customerAddress = $customer->address->present()->fullAddress))
						<p><?php echo $customerAddress; ?></p>
						@endif


						<p> {{$customer->email ?? ''}} </p>

						@if(isset($customer->phones))
							@foreach( $customer->phones as $key => $phone )
							<p>{{ ucfirst($phone->label) }}: {{ phoneNumberFormat($phone->number, $company_country_code) }}
								@if($phone->ext)
								{!! '<br>EXT: '. $phone->ext !!}
								@endif			
							</p>
							@endforeach
						@endif
					</div>
					<div class="job-col customer-container" >

						 @if($secName = $customer->present()->secondaryFullName)
                            <div class="job-address" >
                            	<label class="salesman">Customer Name:</label>
                                <span class="salesman-name">{{$secName}}</span>
                            </div>
                            <div class="clearfix"></div
                        @endif

                        @if((!$customer->is_commercial) && $customer->company_name)
	                        <div class="job-address" >
                            	<label class="salesman">Company Name:</label>
                                <span class="salesman-name">{{$customer->company_name}}</span>
	                        </div>
	                        <div class="clearfix"></div>
                        @endif

						<div class="job-address" ><label class="salesman">Salesman / Customer Rep:</label> 
							<span class="salesman-name">
								@if(isset($customer->rep->first_name) || isset($customer->rep->last_name))
								{{ $customer->rep->first_name ?? '' }} {{ $customer->rep->last_name ?? '' }}
								@else
								Unassigned
								@endif
							</span>
						</div>
						<div class="clearfix"></div>

                        <!-- hide in case of sub contractor login -->
						@if(!\Auth::user()->isSubContractorPrime())
							<?php $referredBy = $customer->referredBy();?>
							@if($customer->referred_by_type == 'customer')
							<div class="job-address">
								<label>Referred by: </label>
								<span class="referred-by-desc salesman-name">{{ $referredBy->first_name ?? ''}} {{ $referredBy->last_name ?? ''}}<i style="font-size: 13px;font-style: normal;"><br>(Existing Customer)</i></span>
							</div>
							@elseif($customer->referred_by_type == 'referral')
							<div class="job-address">
								<label>Referred by: </label> 
								<span class="referred-by-desc salesman-name">{{ $referredBy->name ?? '' }} </span>
							</div>
							@elseif($customer->referred_by_type == 'other')	
							<div class="job-address">
								<label>Referred by: </label>
								<span class="referred-by-desc salesman-name">{{ $customer->referred_by_note }}</span>
							</div>
							@endif
						@endif

						@if($customer->canvasser)
						<div class="job-address">
							<label>Canvasser: </label>
							<span class="referred-by-desc salesman-name">{{ $customer->canvasser }}</span>
						</div>
						<div class="clearfix"></div>
						@endif
						@if($customer->call_center_rep)
						<div class="job-address">
							<label>Call Center Rep: </label>
							<span class="referred-by-desc salesman-name">{{ $customer->call_center_rep }}</span>
						</div>
						@endif
					</div>
				</div>
			<br>
				@if(sizeOf($customer->flags))
				<div class="custom-desc customer-desc desc">
					<p class="flag">
						@foreach($customer->flags as $flag)
						<span class="btn-flags label label-default" style="background-color: {{ $flag->color_for_print }}">{{ $flag->title }}</span>
						@endforeach
					</p>
				</div>
				@endif
				@if(!empty($customer->note))
				<div class="desc customer-desc">
					<p>
						<label class="job-desc-lable">Customer Note: </label>
						<span class="description">{{$customer->note ?? ''}}</span>
					</p>
				</div>
				@endif
			
			<?php 
						$todayDate = \Carbon\Carbon::now(Settings::get('TIME_ZONE'))->toDateString();  
						$appointments = $customer->appointments()->DateRange($todayDate)->get();
						?>
						@if(sizeOf($appointments))
						<div class="desc appointment-container">
							<h3>Appointments </h3>
							<table width="100%">
								@foreach($appointments as $key => $appointment)
								<tr>
									<td valign="top" style="width: 5%;">
										<div class="appointment-sr-container">
											<div class="activity-img notification-badge">
												<div>
													<span>
														<p class="">{{ $key+1 }}</p>
													</span>
												</div>
											</div> 
										</div>
									</td>
									<td style="width: 95%;">
										<div class="job-detail-appointment" >
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
												<div class="appointment-meta" style="width: 100%;">
													<div class="location-box" style="width: 69.5%;">
														<label class="appointment-label">Location: </label>
														<span class="appointment-span-desc">{{ $appointment->location ?? '' }}</span>
													</div>
													<div class="assign_to">
														<label class="appointment-label">Assign To:</label>
														<span class="appointment-span-desc">{{ $appointment->present()->assignedUserName }}</span>
													</div>
													@if($appointment->createdBy)
													<div>
							                            <div class="appointment-desc">
															<label class="appointment-label">Created By: </label>
															<span class="description appointment-span-desc">{{ $appointment->createdBy->full_name }}</span>
														</div>
													</div>
													@endif
												</div>
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
			<div class="clearfix"></div>
			@if($customer->jobs->count())
			<div class="jobs-row  job-heading">
				<h4>
					Jobs
				</h4>
			</div>
			@endif
			<div>
				@foreach ($customer->jobs as $key => $job)
				<div class="jobs-list">
					<div class="jobs-row">
						<div class="job-col">
							<div class="appointment-sr-container" style="position: absolute; left: 3px; top: -2px;">
								<div class="activity-img notification-badge">
									<div>
										<span>
											<p class="">{{ ++$key }}</p>
										</span>
									</div>
								</div> 
							</div>
							<div class="job-address"><label>Job ID:</label><p>{{$job->number}}</p>
								@if($job->isMultiJob())
								<p class="flag job-flag multiproject-id-btn"><span class="btn-flags label label-warning">Multi Project</span></p>
								@endif
								</div>
								@if($job->alt_id)
								<div class="job-address">
									<label>Job #:</label>
									<p style="word-break: break-all;">
										{{$job->full_alt_id}}
									</p>
								</div>
								@endif

								@if($job->address && ($jobAddress = $job->address->present()->fullAddress))
								<div class="job-address"><label>Job Address: </label>
									<p>{!! $jobAddress ?? ''!!}</p>
								</div>
								@endif
								@if($job->duration_in_seconds)	
								<div class="job-address">
									<label>Job Duration: </label>
									<p class="duration">
									{!! $job->present()->jobDuration !!}
									</p>
								</div>		
								@endif
								@if(!$job->isMultiJob())
								<div class="job-address">
									<label>Job Rep / Estimator: </label>
									<p>
										{{ $job->present()->jobEstimators }}
									</p>
								</div>
								<div class="clearfix"></div>
								@endif
								@if(!$job->isMultiJob())
								<div class="job-address">
									<label>Work Crew: </label>
									<p>
										{{ $job->present()->jobRepLaborSubAll }}
									</p>
								</div>
								@endif
							</div>
							<div class="job-col job-detail-part">
								@if(count($job->jobTypes))
								<div class="job-address"><label>Category: </label><p class="upper-text">
									<?php $jobTypes = []; ?>
									@foreach ($job->jobTypes as $jobType)
									<?php $jobTypes[] = $jobType->name ?>
									@endforeach
									{{implode(', ', $jobTypes)}}
								</p></div>
								@endif
								@if(count($job->trades))
								<div class="job-address"><label>Trade Types: </label><p class="upper-text">
									<?php $trades = []; ?>
									@foreach ($job->trades as $trade)
									<?php $trades[] = $trade->name ?>
									@endforeach
									@if(!empty($trades))
									{{implode(', ', $trades)}}
									@endif
								</p></div>
								@endif
								@if(count($job->workTypes))
								<div class="job-address"><label>Work Types: </label><p class="upper-text">
									<?php $workTypes = []; ?>
									@foreach ($job->workTypes as $workType)
									<?php $workTypes[] = $workType->name ?>
									@endforeach
									{{implode(', ', $workTypes)}}
								</p></div>
								@endif
								<div class="job-address"><label>Stage: </label><p>
									<i class="jp-stage-color {{$job->jobWorkflow->stage->color ?? ''}}">{{$job->jobWorkflow->stage->name ?? 'Unknown' }}</i> 
									<br>(Since: 
									@if(isset($job->jobWorkflow->stage_last_modified))
									{{ date(config('jp.date_format'),strtotime($job->jobWorkflow->stage_last_modified)) }})
									@else
									{{''}}
									@endif
								</p></div>
								<div class="job-address">
									<label>Job Record Since: </label>
									<p>
										<?php 
										$dateTime = convertTimezone($job->created_date, Settings::get('TIME_ZONE'));
										?>
										{{ $dateTime->format(config('jp.date_format')) }}
									</p>
								</div>
								<div class="clearfix"></div>
								<div class="job-address">
									<label>Last Modified: </label>
									<p>
										<?php 
										$dateTime = convertTimezone($job->updated_at, Settings::get('TIME_ZONE'));
										?>
										{{ $dateTime->format(config('jp.date_format')) }}
									</p>
								</div>
							</div>
						</div>
						<?php $status = $job->getScheduleStatus(); ?>
						@if( !empty($job->call_required) 
							|| !empty($job->appointment_required) 
							|| sizeOf($job->flags) 
							|| ($status))
						<div class="desc bd-t0" style="padding:15px;">
							<p class="flag job-flag">
								@if($status)
								<span class="btn-flags label label-default">
								{{ $status }}
								</span>
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
						<?php $descStyle = null; ?>
						@if( ($job->isMultiJob() && count($job->projects)) 
						|| $job->description)
						<?php $descStyle = "border-top:0;padding:15px;"?>
						@endif
						
						<div class="desc" style="<?php echo $descStyle; ?>">
							@if($job->description)
							<p class="job-description">
								<label class="job-desc-lable">Job Description: </label>
								<span class="description">{{lineBreak($job->description)}}</span>
							</p>
							@endif
							@if($job->isMultiJob() && count($job->projects))
							<div class="jobs-row project-job-row appointment-container">
								<h3>Projects ({{ count($job->projects) }})</h3>
							</div>
							<div class="project-job-list">
								@foreach($job->projects as $projectKey => $project)
								<div class="jobs-list">
									<div class="jobs-row">
										<div class="job-col" style="width: 40%; padding-left:50px;">
											<div class="appointment-sr-container projects-badge" style="position: absolute; left: 5px; top: -2px;">
												<div class="activity-img notification-badge">
													<div>
														<span>
															<p class="">{{ $key }}.{{ ++$projectKey }}</p>
														</span>
													</div>
												</div> 
											</div>
											<div class="job-address"><label>Project ID:</label><p>{{$project->number}}</p></div>
											@if($project->alt_id)
											<div class="job-address">
												<label>Project #:</label>
												<p style="word-break: break-all;">
													{{$project->full_alt_id}}
												</p>
											</div>
											@endif
											@if($project->duration_in_seconds)	
											<div class="job-address">
												<label>Project Duration: </label>
												<p class="duration">
												{!! $project->present()->jobDuration !!}
												</p>
											</div>		
											@endif
											<div class="clearfix"></div>
											@if($project->estimators)
											<div class="job-address">
												<label>Project Rep / Estimator: </label>
												<p>
													{{ $project->present()->jobEstimators }}
												</p>
											</div>
											<div class="clearfix"></div>
											@endif
											<div class="job-address">
												<label>Work Crew: </label>
												<p>
													{{ $project->present()->jobRepLaborSubAll }}
												</p>
											</div>
										</div>
										<div class="job-col job-detail-part">
											@if($project->projectStatus)
												<div class="job-address"><label>Project Status: </label><p>
													{{ $project->projectStatus->name ?? '' }}
												</p></div>	
											@endif
											@if(count($project->jobTypes))
											<div class="job-address"><label>Category: </label><p class="upper-text">
												<?php $jobTypes = []; ?>
												@foreach ($project->jobTypes as $jobType)
												<?php $jobTypes[] = $jobType->name ?>
												@endforeach
												{{implode(', ', $jobTypes)}}
											</p></div>
											@endif
											
											<div class="job-address"><label>Trade Type: </label><p class="upper-text">
												<?php $trades = []; ?>
												@foreach ($project->trades as $trade)
												<?php $trades[] = $trade->name ?>
												@endforeach
												@if(!empty($trades))
												{{implode(', ', $trades)}}
												@endif
											</p></div>

											@if(count($project->workTypes))
											<div class="job-address"><label>Work Type: </label><p class="upper-text">
												<?php $workTypes = []; ?>
												@foreach ($project->workTypes as $workType)
												<?php $workTypes[] = $workType->name ?>
												@endforeach
												{{implode(', ', $workTypes)}}
											</p></div>
											@endif
											<div class="job-address">
												<label>Project Record Since: </label>
												<p>
													<?php 
													$dateTime = new Carbon\Carbon($project->created_date,'UTC');
													$dateTime->setTimeZone(Settings::get('TIME_ZONE'));
													?>
													{{ $dateTime->format(config('jp.date_format')) }}
												</p>
											</div>
											<div class="clearfix"></div>
											<div class="job-address">
												<label>Last Modified: </label>
												<p>
													<?php 
													$dateTime = new Carbon\Carbon($project->updated_at,'UTC');
													$dateTime->setTimeZone(Settings::get('TIME_ZONE'));
													?>
													{{ $dateTime->format(config('jp.date_format')) }}
												</p>
											</div>
										</div>
									</div>
									@if($project->description)
									<div class="desc">
										<p>
											<label class="job-desc-lable">Project Description: </label>
											<span class="description">{{lineBreak($project->description)}}</span>
										</p>
									</div>
									@endif
								</div>
								@endforeach
							</div>
							@endif
						</div>
					</div>
					<div class="separator"></div>
					@endforeach
				</div>
			</div>
		</div>
	</body>
	</html>