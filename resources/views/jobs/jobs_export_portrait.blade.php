<!DOCTYPE html>
<html class="no-js" ng-app="jobProgress" ng-controller="AppCtrl">
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
		}
		p {
			margin: 0;
		}
		h1,h2,h3,h4,h5,h6 {
			margin: 0;
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
			border: 1px solid #eee;
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
		}
		.job-col {
			border-right: 1px solid #eee;
			display: inline-block;
			font-size: 18px;
			margin-top: 18px;
			padding: 0 18px;
			vertical-align: top;
			width: 45%;
		}
		.job-col:last-child {
			border-color: transparent;
		}
		.job-detail-part label {
			width: 48%;
			display: inline-block;
			vertical-align: top;
			margin-bottom: 10px;
			font-weight: bold;
		}
		.job-detail-part p {
			display: inline-block;
			vertical-align: top;
		}
		.upper-text {
			text-transform: uppercase;
		}
		.desc {
			margin: 0 15px;
			padding: 15px 0;
			border-top: 1px solid #eee;
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
		}
		.desc p strong {
			font-size: 18px;
		}
		.separator {
			border: 1px solid #dfdfdf;
		}
		.job-address label{
			float: left;
		}
		.job-address p {
			display: block;
			margin-left: 48%;
    
		}
		.stage i {
			font-style: normal;
		}
		.job-address span{
			display: block;
		    margin-left: 105px;
		    white-space: normal;
		}
		.company-logo {
			width: 65px;
		    height: 65px;
		    border-radius: 8px;
		    border: 1px solid #ddd;
		    background: #fff;
		    text-align: center;
		    line-height: 53px;
		    display: inline-block;
		    padding: 4px;
		    vertical-align: middle;
		    overflow: hidden;
		    box-sizing: border-box;
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
		.job-detail-part label.work-type-label{
			float: left;
			width: 137px;
		}
		.job-detail-part p.work-type-values{
			display: block;
			padding-left: 137px;
		}
		.legend > span{
			float: right;
			font-size: 18px;
		}
		.upcoming-appointment-title {
			display: inline-block;
			margin-bottom: 5px;
		}
		.job-detail-appointment {
		    border-bottom: 1px solid #eee;
		    cursor: pointer;
		    padding: 10px 0;
		    font-size: 18px;
		}
		.job-detail-appointment:last-child {
			border-bottom: none;
		}
		.text-right{
			text-align: right;
		}
		.today-date p{
			text-align: right;
			padding-bottom: 3px;
		}
		.text-right{
	        float: right;
	    }
	   
	    .location-box{
	        display: inline-block;
	    }
	    
		p.flag{
	        padding-bottom: 5px;
	    }
	    p.flag span{
	    	/*margin-bottom: 5px;
	    	display: inline-block;
	    	line-height: normal;*/
	    }
	    .appointment-meta{
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
	    }
	    td .appointment-sr-container{
	        padding: 10px 0px;  
	    }
		.activity-img p{
			padding: 4px 0;
			font-size: 18px;
			color: #666;
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
	    .appointment-sr-container .activity-img p {
		    padding: 4px 0;
		    font-size: 18px;
		    color: #666;
		    text-align: center;
		}	
		.appointment-desc label, .location-box label {
	        font-weight: bold;
	    }
		.assign_to {
	        font-weight: bold;
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
					<h1 style="width:84%;">{{$company->name ?? ''}}</h1>
					<p class="company-name">Jobs Export</p>
				</div>
				<div class="main-logo">
					<img src="{{asset('main-logo.png')}}">
					<div class="today-date">
						<p><label>Current Date: </label><?php echo  \Carbon\Carbon::now(Settings::get('TIME_ZONE'))->format(config('jp.date_format')) ; ?></p>
					</div>
				</div>
				<div class="clearfix"></div>
				@if(isset($filters['start_date']) AND isset($filters['end_date']))
				<div class="filters-section">
					<h5>Filters Applied</h5>
					<p class="date-format">
							{{ date("m/d/Y",strtotime($filters['start_date'])) }} - {{ date("m/d/Y",strtotime($filters['end_date'])) }}
					</p>
				</div>
				@endif
				<div>
					@foreach ($jobs as $key => $job)
					<div class="jobs-list">
						<div class="jobs-row">
							<div class="job-col">
								<h3>{{ $job->customer->first_name ?? 'Unknown' }} {{ $job->customer->last_name ?? '' }}</h3>
								<p>
								@if(($job->customer) 
								&& ($customerAddress = $job->customer->address->present()->fullAddress))
									<?php echo $customerAddress; ?>
								@endif
								</p>
								<p> {{ $job->customer->email ?? '' }} </p>
								@if(isset($job->customer->phones))
								@foreach( $job->customer->phones as $key => $phone )
									<p>
										{{ ucfirst($phone->label) }}: {{ phoneNumberFormat($phone->number, $company_country_code) }}
										@if($phone->ext)
										  {!! '<br>EXT: '. $phone->ext !!}
										@endif	
									</p>
								@endforeach
								@endif
								<p class="rep"><label>Salesman / Customer Rep: </label><span>
								@if(isset($job->customer->rep->first_name) || isset($job->customer->rep->last_name))
									{{ $job->customer->rep->first_name ?? '' }} {{ $job->customer->rep->last_name ?? '' }}
								@else
									Unassigned
								@endif
								</span></label></p>

								<?php $referredBy = $job->customer->referredBy(); ?>
								@if($job->customer->referred_by_type == 'customer')
									<p class="rep"><label>Referred by: <span>{{ $referredBy->first_name ?? ''}} {{ $referredBy->last_name ?? ''}}</span></label></p>
								@elseif($job->customer->referred_by_type == 'other')
									<p class="rep"><label>Referred by: <span>{{ $job->customer->referred_by_note ?? ''}}</span></label></p>
								@elseif($job->customer->referred_by_type == 'referral')
									<p class="rep"><label>Referred by: <span>{{ $referredBy->name ?? ''}}</span></label></p>
								@endif
								@if(sizeOf($job->customer->flags))
									<p class="flag">
									@foreach($job->customer->flags as $flag)
										<span class="btn-flags label label-default" style="background-color: {{ $flag->color_for_print }}">{{ $flag->title }}</span>	
									@endforeach
									</p>
								@endif

							</div>
							<div class="job-col job-detail-part">
								<div>
									<label>Job ID: </label>
									<p>{{ $job->number ?? ''}}	
									<?php
									   $todayAppointments = $job->appointments()->today()->get();
									   $upcomingAppointment  = $job->appointments()->upcoming()->get()->count();
									?>
								    @if(!empty($todayAppointments->count()) )
								  		<i class="fsz14 col-red fa fa-calendar"></i>
								   	@elseif ( empty($todayAppointments->count()) && !empty($upcomingAppointment) ) 
									    <i class="fsz14 col-black fa fa-calendar"></i>
								    @endif
									</p>
								</div>
								@if($job->alt_id)
								<div>
									<label>Job #: </label>
									<p style="word-break: break-all;">
										{{ $job->full_alt_id }}
									</p>
								</div>
								@endif
								@if(count($job->jobTypes))
									<?php $jobTypes = []; ?>
									@foreach($job->jobTypes as $jobType)
										<?php $jobTypes[] = $jobType->name; ?>
									@endforeach	
									<div class="job-address"><label>Category: </label><p class="upper-text">{{ implode(', ', $jobTypes) }} </p></div>
								@endif
								@if(isset($job->trades))
								<div class="job-address"><label>Trade Types: </label><p class="upper-text">
									{{implode(', ', $job->trades->pluck('name')->toArray())}}
								</p></div>
								@endif

								@if(count($job->workTypes))
									<?php $workTypes = []; ?>
									@foreach($job->workTypes as $workType)
									<?php $workTypes[] = $workType->name; ?>
									@endforeach
									<div ><label>Work Types: </label><p class="upper-text">{{ implode(', ', $workTypes) }} </p></div>
								@endif
								<div class='stage job-address'><label>Stage: </label><p>
								<i class="jp-stage-color {{ $job->jobWorkflow->stage->color ?? ''}}">{{$job->jobWorkflow->stage->name ?? 'Unknown' }}</i><br>
								(Since: 
								@if(isset($job->jobWorkflow->stage_last_modified))
								{{ date(config('jp.date_format'),strtotime($job->jobWorkflow->stage_last_modified)) }})
								@else
								{{ $job->jobWorkflow->stage_last_modified ?? ''}})
								@endif
								</p></div>
								@if($job->address && ($jobAddress = $job->address->present()->fullAddress))
									<div class="job-address"><label>Job Address: </label>
										<p>
											<?php echo $jobAddress; ?>
										</p>
									</div>
								@endif

								<div class="job-address">
									<label>Job Rep / Estimator: </label>
									<p>{{ $job->present()->jobEstimators }}</p>	
								</div>

								<div class="job-address">
									<label>Work Crew: </label>
									<p>
										{{ $job->present()->jobRepLaborSubAll }}
									</p>	
								</div>

								@if( !empty($job->call_required) || !empty($job->appointment_required) || sizeOf($job->flags))
									<p class="flag">
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
								@endif
							</div>
						</div>
                   		@if(!empty($job->customer->note))
							<div class="desc">
								<p>
								<strong>Customer Note: </strong>
								<span class="description">{{ $job->customer->note ?? ''}}</span></p>
							</div>
						@endif
						 <?php 
	                        $todayDate = \Carbon\Carbon::now(Settings::get('TIME_ZONE'))->toDateString();  
	                        $appointments = $job->appointments()->DateRange($todayDate)->get();
	                    ?>
						@if(sizeOf($appointments))
		                    <div class="desc appointment-container">
		                        <h3>Appointments </h3>
		                        <table style="width: 100%">
		                        @foreach($appointments as $key => $appointment)
		                            <tr>
		                                <td valign="top">
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
		                                <td>
		                                    <div class="job-detail-appointment">
		                                        <div class="cust-address">  
		                                            <div>
		                                                <p class="upcoming-appointment-title">
		                                                    <span>{{ $appointment->title ?? ''}}</span>
		                                                </p>
		                                                <div class="pull-right">
		                                                    <span>
		                                                        <?php 
		                                                            $dateTime = new Carbon\Carbon($appointment->start_date_time,'UTC');
		                                                            $dateTime->setTimeZone(\Settings::get('TIME_ZONE'));
		                                                        ?>
		                                                        {{ $dateTime->format(config('jp.date_time_format')) }}
		                                                    </span>
		                                                </div>
		                                            </div>
		                                            <div class="appointment-meta">
		                                               <div class="location-box">
		                                                    <label>Location: </label>
		                                                    <span>{{ $appointment->location ?? '' }}</span>
		                                                </div>
		                                                <div class="assign_to text-right"> Assign To:
		                                                    <span>{{ $appointment->present()->assignedUserName }}</span>
		                                                </div>
		                                            </div>
													@if($appointment->createdBy)
														<div>
								                            <div class="appointment-desc">
																<label class="appointment-label">Created By: </label>
																<span class="description appointment-span-desc">{{ $appointment->createdBy->full_name }}</span>
															</div>
														</div>
													@endif
		                                            <div>
		                                                <div class="appointment-desc">
		                                                    <label>Note: </label>
		                                                    <span class="description">{{ $appointment->description ?? '' }}</span>
		                                                </div>
		                                            </div>
		                                        </div>
		                                    </div>
		                                </td>
		                            </tr>
		                        @endforeach
                      			</table>
		                    </div>
                   		 @endif
						@if(!empty($job->description))
						<div class="desc">
							<p><strong>Job Description: </strong>
								<span class="description">{{ lineBreak($job->description)}}</span>
							</p>
						</div>
						@endif
					</div>
					<div class="separator"></div>
					@endforeach
				</div>
			</div>
		</div>
</body>
</html>