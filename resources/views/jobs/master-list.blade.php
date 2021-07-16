<!DOCTYPE html>

<html class="no-js"><head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title> JobProgress </title>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
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
		width: 40.5%;
		vertical-align: top;
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
		display: inline-block;
	    width: 30%;
	    text-align: right;
	    vertical-align: top;
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
		/*page-break-inside: avoid;*/
	}
	.table th {
		border-bottom: 1px solid #ccc;
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

	.work-heading {
  		/*text-align: center;*/
  		width: 28.5%;
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
    /*.work-heading h2 {
	    margin-left: 15px;
	}*/

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
	 .table th, .table td, .table tr.page-break td td {
	 	padding: 10px;
	 }
	.master-list-note{
		width: 50%;
		word-wrap: break-word;
		text-align: justify;
		white-space: pre-wrap;
	}
	 /*to avoid repeating header*/
	thead, tfoot { display: table-row-group }	
/*	.date-format span {
		margin-right: 20px;
	}*/
	.date-format span {
        margin-right: 5px;
        background: #e8e8e8;
        font-size: 13px;
        border-radius: 3px;
        padding: 3px 6px;
        display: inline-block;
        margin-bottom: 5px;
    } 
    .new-page {
    	 /*page-break-inside: avoid;*/
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
				</div>
				<div class="work-heading">
					<h2>Master List Report</h2>
				</div>
				<div class="main-logo">
					<img src="{{asset('main-logo.png')}}">
					<div class="today-date">
						<p><label>Current Date: </label><?php echo  \Carbon\Carbon::now(Settings::get('TIME_ZONE'))->format(config('jp.date_format')) ; ?></p>
					</div>
				</div>
				<div class="clearfix"></div>
				@if((ine($filters, 'start_date') && ine($filters, 'end_date'))
					|| ine($filters, 'priority') || ($applyFilter) || ine($filters, 'job_cities'))
					
				<div class="filters-section">
					<!-- <span class="label label-default">Default</span> -->
					<h5>Filters Applied</h5>
					<p class="date-format">

							<!-- duration -->
							@if(ine($filters, 'start_date') && ine($filters, 'end_date'))
							<span><b>Duration:</b> 	{{ date(config('jp.date_format'),strtotime($filters['start_date'])) }} - {{ date(config('jp.date_format'),strtotime($filters['end_date'])) }}</span>
							@endif

							<!-- stages -->
							@if($stages && $stages->count())
							<span class="stage"><b>Stage(s): </b>
							@foreach($stages as $stage)
								<i class="jp-stage-color {{ $stage->color }}">{{ $stage->name }}@if($stages->last()->name != $stage->name), @endif</i>
							@endforeach	
							</span>
							@endif

							<!-- trades -->
							@if($trades && $trades->count())
							<?php $t = []; ?>
							@foreach($trades as $trade)
							<?php $t[] = $trade->name; ?>
							@endforeach
							<span><b>Trade(s):</b> {{ implode(', ', $t) }}</span>
							@endif

							<!-- worktypes -->
							@if($workTypes && $workTypes->count())
							<?php $wt = []; ?>
							@foreach($workTypes as $workType)
							<?php $wt[] = $workType->name; ?>
							@endforeach
							<span><b>WorkType(s):</b> {{ implode(', ', $wt) }}</span>
							@endif

							<!-- priority -->
							@if(ine($filters, 'priority'))
							<?php 
							$priority = [];
							foreach ((array)$filters['priority'] as $prarity) {
								$priority[] = ucfirst($prarity);
							}
							?>
							<span><b>Priority(s):</b> {{ implode(', ', $priority) }}</span>
							@endif

							<!-- company crew -->
							@if($jobReps && $jobReps->count())
							@foreach($jobReps as $rep)
							<?php $jr[] = $rep->full_name; ?>
							@endforeach
							<span><b>Company Crew:</b> {{ implode(', ', $jr) }}</span>
							@endif

							<!-- sub contractors -->
							@if($subContractors && $subContractors->count())
							<?php $subs = []; ?>
							@foreach($subContractors as $sub)
							<?php $subs[] = $sub->full_name; ?>
							@endforeach
							<span><b>Sub(s):</b> {{ implode(', ', $subs) }}</span>
							@endif

							<!-- customer reps -->
							@if($customerReps && $customerReps->count())
							<?php $cr = []; ?>
							@foreach($customerReps as $rep)
							<?php $cr[] = $rep->full_name; ?>
							@endforeach
							<span><b>Salesman / Customer Rep(s):</b> {{ implode(', ', $cr) }}</span>
							@endif

							<!-- estimators -->
							@if($estimators && $estimators->count())
							<?php $esti = []; ?>
							@foreach($estimators as $estimator)
							<?php $esti[] = $estimator->full_name; ?>
							@endforeach
							<span><b>Job Rep / Estimator(s):</b> {{implode(', ', $esti) }} </span>
							@endif

							<!-- job cities -->
							@if(ine($filters, 'job_cities'))
							<?php $cities = []; ?>
							@foreach ((array)$filters['job_cities'] as $city)
								<?php $cities[] = ucfirst($city); ?>
							@endforeach
							<span><b>Cities:</b> {{ implode(', ', $cities) }} </span>
							@endif

					</p>
				</div>
				@endif
				<table class="table">
					<thead>
						<tr>
							<th style="width: 6%;">#</th>
							<th style="width: 20%;">Customer / Job ID</th>
							<th style="width: 25%;">Job Price</th>
							<th style="width: 15%;">Stage</th>
							<th style="width: 12%;">Priority</th>
							@if(ine($filters, 'contract_signed_date'))
							<th style="width: 16%";>Contract Signed Date</th>
							<th style="width: 20%;" class="customer-rep-heading">Notes</th>
							@else
							<th style="width: 36%;" class="customer-rep-heading">Notes</th>
							@endif
							<th style="width: 15%;">Note Date</th>
						</tr>
					</thead>
					<tbody>
						<?php $sr = 1; ?>
						@foreach ($jobs as $key => $job)
						<tr class="page-break" style="background-color: #fff;">
							<td style="width: 6%;">{{$sr++}} </td>
							<td style="width: 20%;">
								<span class="job-heading">{{ $job->customer->first_name ?? '' }} {{ $job->customer->last_name ?? '' }} /</span> <span style="white-space: nowrap;margin-left: -5px;">{{ $job->number}}</span>
							</td>
							<td style="width: 12%;" class="stage">
								{{ showAmount($job->amount) }}
							</td>
							<?php $jobStage = $job->getCurrentStage(); ?>
							<td style="width: 12%;" class="stage">
								<i style="font-size:18px;" class="jp-stage-color {{ $jobStage['color'] ?? '' }}">{{ $jobStage['name'] ?? 'Unknown' }}</i>
								<br>
							</td>
							<td style="width: 12%;" class="stage">
							{{ ucfirst($job->priority) }}
							</td>
							@if(ine($filters, 'contract_signed_date'))
							<td class="stage master-list-note">@if($job->cs_date){{ date(config('jp.date_format'),strtotime($job->cs_date)) }}@else -- @endif
								</td>
							<td class="stage master-list-note"><div class="new-page">@if($job->note){{ lineBreak($job->note) }}@else -- @endif</div></td>
							@else
							<td class="stage master-list-note"><div class="new-page">@if($job->note){{ lineBreak($job->note) }}@else -- @endif</div></td>
							@endif
							<td style="width: 12%;" class="stage">
								@if($job->note_date)
								{{ \Carbon\Carbon::parse($job->note_date)->format(config('jp.date_format')) }}
								@endif	
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
	</body>
</html>
