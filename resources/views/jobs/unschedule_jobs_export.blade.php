<!DOCTYPE html>

<html class="no-js"><head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title> JobProgress </title>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
<link rel="stylesheet" href="{{config('app.url')}}css/main.css" />
<meta name="viewport" content="width=device-width">
<style type="text/css">
	body {
		background: #000;
		margin: 0;
		font-family: Helvetica,Arial,sans-serif;
		font-size: 18px;
		color: #333;
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
	    margin-left: 12px;
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
	.second-part{
		border-top: 1px solid #eee;
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
	    border-bottom: 1px solid #eee;
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
  	td {
  		vertical-align: top;
  	}
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
  	.description {
		text-align: justify;
		white-space: pre-wrap;
	}
/*	.appointment-desc label, .location-box label {
        font-weight: bold;
    }*/
	/*.assign_to {
        font-weight: bold;
    }*/
    .work-heading h2 {
	    margin-left: 15px;
	}
	p, span {
		font-weight: normal;
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
	.appointment-meta .assign_to {
		margin: 3px 0;
	}	
	.appointment-label {
	 	float: left; 
	 	width: 110px;
	 }
	 .appointment-span-desc {
	 	display: block; 
	 	margin-left: 110px;
	 }
	 .appointment-container p {
	 	font-size: 18px;
	 }
	 /*to avoid repeating header*/
		thead, tfoot { display: table-row-group }
	</style>
	<style type="text/css"></style></head>
	<body>
		<div class="container">
			<div class="jobs-export">
				<div class="header-part">
					@if(! empty($company->logo) )
					<div class="company-logo">
						<img src="{{ FlySystem::getUrl(config('jp.BASE_PATH').$company->logo) }}" />
					</div>
					@endif
					<h1 style="width:70%;">{{$company->name ?? ''}}</h1>
				</div>
				<div class="work-heading">
				<h2>Work Order</h2>
					<p class="company-name" style="padding-left: 15px;">Unscheduled Jobs</p>
				</div>
				<div class="main-logo">
					<img src="{{asset('main-logo.png')}}">
					<div class="today-date">
						<p><label>Current Date: </label><?php echo  \Carbon\Carbon::now(Settings::get('TIME_ZONE'))->format(config('jp.date_format')) ; ?></p>
					</div>
				</div>
				<div class="clearfix"></div>
				<table class="table">
					<thead>
						<tr>
							<th style="width: 4%;">Sr.No</th>
							<th style="width: 12%;">Customer Name</th>
							<th style="width: 6%;">Job #</th>
							<th style="width: 17%;">Job Address</th>
							<th style="width: 13%;">Customer Info.</th>
							<th style="width: 10%;">Trade Type</th>
							<th style="width: 10%;">Stage</th>
							<th style="width: 9%;" class="customer-rep-heading">Salesman</th>
							<th style="width: 10%;">Job Rep / Estimator</th>
							<th style="width: 9%;">Work Crew</th>
						</tr>
					</thead>
					<tbody>
						<?php $sr = 1; ?>
						@foreach ($jobs as $key => $job)

						<tr class="page-break">
							<td colspan="10">
								<table style="width: 100%;">
									<tr>
										<td style="width: 4%;">{{$sr++}} </td>
										<td style="width: 12%;">
											<?php 
											$secName = $job->customer->present()->secondaryFullName();
											$todayAppointments = $job->appointments()->today()->get();
										    $upcomingAppointment  = $job->appointments()->upcoming()->get()->count();
											?>
											<span class="job-heading">{{ $job->customer->first_name ?? '' }} {{ $job->customer->last_name ?? '' }}</span> <br> 
											<p>{{ $job->number ?? ''}}	
												@if((!$secName) && !empty($todayAppointments->count()) )
											  		<i class="fsz14 col-red fa fa-calendar"></i>
											   	@elseif ( (!$secName) 
											   		&& empty($todayAppointments->count()) 
											   		&& !empty($upcomingAppointment) ) 
												    <i class="fsz14 col-black fa fa-calendar"></i>
											    @endif
											</p>
											@if($secName)
											<p style="font-size: 13px">({{$secName}})
												@if(!empty($todayAppointments->count()) )
											  		<i class="fsz14 col-red fa fa-calendar"></i>
											   	@elseif ( empty($todayAppointments->count()) && !empty($upcomingAppointment) ) 
												    <i class="fsz14 col-black fa fa-calendar"></i>
											   @endif
											</p>
											@endif
										</td>

										<td style="word-break: break-all;width: 6%;">
											{{ $job->full_alt_id }}
										</td>
										<td style="width:17%;">
											@if($job->address && ($jobAddress = $job->address->present()->fullAddress))

												{!! $jobAddress ?? '' !!} 	
											@endif
										</td>

										<td style="word-break: break-all;width: 13%;">

											<p> 
												@if($job->customer->email)
												{{ $job->customer->email ?? '' }} <br/>
												@endif
												@if(isset($job->customer->phones))
													@foreach($job->customer->phones as $phone)
														({{substr(ucfirst($phone->label), 0,1)}}) {{ phoneNumberFormat($phone->number, $company_country_code) }} 
														@if($phone->ext)
														  {!! '<br>EXT: '. $phone->ext !!}
														@endif	
														<br/>
													@endforeach
												@endif
											</p>
											
										</td>
										<td class="trades" style="width: 10%;">
											@if(isset($job->trades))
												{{implode(', ', $job->trades->pluck('name')->toArray())}}
											@endif
										</td>
										<td style="white-space:nowrap;width: 10%;" class="stage">
											@if(!$job->isProject())
												<i style="font-size:18px;" class="jp-stage-color {{ $job->jobWorkflow->stage->color ?? '' }}">{{ $job->jobWorkflow->stage->name ?? 'Unknown' }}</i>
												<br>
												<i style="font-size:13px;">
												(Since: 
												@if(isset($job->jobWorkflow->stage_last_modified))
												{{ date(config('jp.date_format'),strtotime($job->jobWorkflow->stage_last_modified)) }})
												@else
												{{ $job->jobWorkflow->stage_last_modified ?? ''}})
												@endif
												</i>
											@endif
										</td>
										<td style="width: 9%;">
											@if(isset($job->customer->rep->first_name) || isset($job->customer->rep->last_name))
												{{ $job->customer->rep->first_name ?? '' }} {{ $job->customer->rep->last_name ?? '' }}
											@else
												Unassigned
											@endif
										</td>
										<td class="reps" style="width: 10%;">
											{{ $job->present()->jobEstimators }}
										</td>
										
										<td class="reps" style="width: 9%;">
											{{ $job->present()->jobRepLaborSubAll }}
										</td>
									<tr>
										<td></td>
										<td colspan="10">
											@if( isset($job->customer->flags) 
											&& sizeOf($job->customer->flags))
											<p class="flag customer-flags">
												@foreach($job->customer->flags as $flag)
												<span class="btn-flags label label-default" style="background-color: {{ $flag->color_for_print }}">{{ $flag->title }}</span>
												@endforeach
											</p>
											@endif
										</td>
									</tr>
									<tr>

										<td></td>
										<td colspan="10">
										<?php
										$todayDate = \Carbon\Carbon::now(Settings::get('TIME_ZONE'))->toDateString();  
					                    $appointments = $job->appointments()->DateRange($todayDate)->get();
					                    ?>	
										@if(sizeOf($appointments) 
											|| !empty($job->appointment_required)
											|| !empty($job->call_required)
											|| ($job->work_crew_notes))
											<div class="second-part">
												@if(!empty($job->work_crew_notes))
												<div class="desc customer-desc">
													<label>Work Crew Notes:</label> 
													<span class="description">{{ lineBreak($job->work_crew_notes) }}</span>
												</div>
												@endif
												
												@if($job->call_required || $job->appointment_required || sizeOf($job->flags))
													<p class="flag job-flags">
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
												@if(sizeOf($appointments))
												<div class="desc appointment-container">
													<p class="job-heading">Appointments </p>
													@foreach($appointments as $key => $appointment)
													<div class="appointment-sr-container">
													    <div class="activity-img notification-badge">
													    	<p class="">{{ $key+1 }}</p>
													    </div> 
													</div>
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
															<div class="appointment-meta">
							                                   <div class="location-box">
							                                        <label class="appointment-label">Location: </label>
							                                        <span class="appointment-span-desc">{{ $appointment->location ?? '' }}</span>
							                                    </div>
							                                    <div class="assign_to"> 
							                                    <lable class="appointment-label">Assign To: </lable>
							                                        <span class="appointment-span-desc">{{ $appointment->present()->assignedUserName }}</span>
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
							                                @if($appointment->description)
							                                <div>
							                                    <div class="appointment-desc">
							                                        <label class="appointment-label">Appointment Note: </label>
							                                        <span class="description appointment-span-desc">{{ $appointment->description }}</span>
							                                    </div>
							                                </div>
							                                @endif
														</div>
													</div>
													@endforeach
												</div>
												@endif
											</div>
										@endif
										</td>
									</tr>
								</table>
							</td>
						</tr>
						@endforeach
					</tbody>
				</table>
				
				@if(!count($jobs))
				<div class="no-record">No Records Found</div>
				@endif	
			</div>
		</div>

	</body></html>