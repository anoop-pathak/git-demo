<!DOCTYPE html>
<html class="no-js"><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

<title> JobProgress </title>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
<link rel="stylesheet" href="{{config('app.url')}}css/main.css" />
<link rel="stylesheet" href="{{config('app.url')}}css/vendor.879fa015.css">
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
		padding-bottom: 0;
	}
	h1 {
		margin: 4px 0;
		font-size: 26px;
		font-weight: normal;
	}
	.header-part {
		display: inline-block;
		width: 33%;
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
		width: 80%;
		font-size: 22px;
	}
	.clearfix {
		clear: both;
	}
	.main-logo {
		display: inline-block;
		width: 33%;
		text-align: right;
		vertical-align: top;
	}
	.main-logo img {
		opacity: 0.6; 
		width: 200px;
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
		margin-bottom: 0;
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
	.table tr.page-break:nth-child(2n+1) {
		background-color: #f9f9f9;
	}
	.table th, .table tr.page-break td td {
		padding: 10px 3px;
	}
	.table td {
		font-size: 14px;
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
		font-weight: normal;
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
    .filled {
    	background-color: #bbbbbb;
    }
    .pb-legend {
    	float: right;
    }
    .pb-legend span {
    	border: 1px solid #ccc;
	    border-radius: 2px;
	    display: inline-block;
	    font-size: 15px;
	    margin-bottom: 20px;
	    margin-left: 5px;
	    padding: 2px 5px;
	    text-transform: capitalize;
	    white-space: nowrap;
    }
    .work-heading {
      	text-align: center;
      	width: 33%;
      	display: inline-block;
      	vertical-align: top;
      	margin-top: 5px;
      }
      .work-heading h2 {
      	/*margin-left: 15px;*/
      }
    /*to avoid repeating header*/
    /*thead, tfoot { display: table-row-group }*/
    table.table.dataTable th, table.table.dataTable td {
    	text-align: center !important;
    	text-transform: capitalize;
    }
    table.table.dataTable td.customer-detail-sec {
    	text-align: left !important;
    	vertical-align: top;
    }
    .color-legend {
    	/*margin-left: 36px;*/
    	margin-bottom: 10px;
    }
    .color-legend .completed,
    .color-legend .uncompleted {
    	position: relative;
    	padding-left: 10px;
    	padding-right: 10px;
    	font-size: 15px;
    }
    .color-legend .completed:before,
    .color-legend .uncompleted:before {
    	/*content: "";*/
    	border: 1px solid #ccc;
    	background: #fff;
    	height: 15px;
    	width: 15px;
    	position: absolute;
    	left: 0;
    	top: 50%;
    	margin-top: -8px;
    }
    .color-legend .completed:before {
    	background: #f2f2f2;
    }
    .email-field {
		word-break: break-all;
	}
	.entity-data {
		overflow: hidden;
		text-overflow: ellipsis;
	}
	.job-id-replace {
		display: inline-block;
		width: 100%;
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
				<h1>{{ $company->name }}</h1>
			</div>
			<div class="work-heading">
				<h2>Progress Board</h2>
				<div class="color-legend">
					<span class="completed">{{ $board->name }}</span>
				</div>
			</div>
			<div class="main-logo">
				<img src="https://www.jobprogress.com/wp-content/themes/jobprogress/images/main-logo-grey.png">
				<div class="today-date">
					<p>
						<label>Current Date: </label>
						<?php echo  \Carbon\Carbon::now(Settings::get('TIME_ZONE'))->format(config('jp.date_format')) ; ?>
					</p>
				</div>
				<div class="legend">
					<span></span>
				</div>
			</div>
			<div class="clearfix"></div>

			<div class="pb-legend">

				<!-- show column name and short name -->
				@foreach ($columns as $column)
					<span>{{ getFirstLetters($column) }} - {{ $column }}</span>
				@endforeach
			</div>
			<table class="table data-table dataTable table-full-width table-bordered">
				<thead>
					<tr>
						<th style="width: 5%">#</th>
						<th style="width: 25%">Customer/Job</th>
						<!-- create table heading -->
						@foreach ($columns as $column)
							<th>{{ getFirstLetters($column) }} </th>
						@endforeach
					</tr>
				</thead>
				<tbody>
					<?php $sr = 0; ?>
					@foreach($pbJobs as $key => $job)

					<tr class="page-break" style="background-color: #fff;">
						<td style="width: 3%;vertical-align: top;">{{ ++$sr }} </td>
						<td style="width: 25%" class="customer-detail-sec">
							<span class="ng-binding">
								<?php 
									$customer = $job->customer
								?>
								<span class="job-id-replace">
								{{ $customer->first_name .' '. $customer->last_name }} / {{ $job->present()->jobIdReplace }}
								</span>
								
							</span>
							<br><span>
								<?php 
								$trades = $job->trades->pluck('name')->toArray();
									echo implode(', ', $trades)
								?>
							</span>
							<br>
							@if(($job->address)
								&& ($jobAddress = $job->address->present()->fullAddressOneLine()))
								<span>
								{!! $jobAddress !!}
								</span>
								<br>
								@endif
								<span><i class="jp-stage-color {{ $job->jobWorkflow->stage->color }}">{{ $job->jobWorkflow->stage->name }}</i><span style="margin-left:0; ">
								@if($job->alt_id)
								 Stage, <b>@if($job->isProject()) Project @else Job @endif #:</b> {{ $job->full_alt_id }}
								@else
								Stage
								@endif
								</span>
							</span>
							<br>
							<span><b>S/CR:</b> {{ $customer->present()->salesman }}</span>
						</td>
						<?php
						// get entries of job
						$jobEntries = $job->productionBoardEntries()
							->with('task.participants')
							->get()
							->groupBy('column_id');

						// get job columns ids
						$jobColumnIds = $jobEntries->keys()->all();
						?>

						<!-- add all table desription of all columns -->
						@foreach($columns as $id => $column)

							@if(in_array($id, $jobColumnIds))
							<?php  
								$data = json_decode($jobEntries[$id][0]['data']);
								$color = $jobEntries[$id][0]['color'];
							?>
								@if(!isset($data->value) 
										|| !isset($data->type)) 
									<td class="entity-data" style="background-color: {{ $color  }}"> .. </td> <?php continue; ?>
								@endif
								<td  class="text-left entity-data" style="background-color: {{ $color  }}">
							<div class="text-left">
							<?php
								$taskDetail = '';

								if ($task = $jobEntries[$id][0]['task']) {
									$status 	  = $task->completed ? 'completed' : null;
									$participants = implode(',', $task->participants->pluck('full_name')->toArray());

									$taskDetail   = "<br>Task Detail :-
									<br>Title : {$task->title}";
									if ($task->is_high_priority_task) {
										$taskDetail .= "<br>Priority : {$task->isHighPriorityTask()}";
									}
									$taskDetail .= "<br>Assigned To : $participants";

									if ($task->due_date && !$status) {
										$taskDetail .= "<br>Due Date : $task->due_date";
									}elseif($status) {
										$taskDetail .= "<br>Status : $status";
									}
								}

								switch ($data->type) {
									case 'markAsDone':
										echo '<i class="fa fa-fw fa-check"></i>'.$taskDetail;
										break;
									case 'date':
										echo $data->value.$taskDetail;
										break;
									case 'input_field':
										echo $data->value.$taskDetail;
										break;
									default:
										if($taskDetail)	{
											echo $taskDetail;
										}else {
											echo '..';
										}
										break;
								}
							?>
							</div>
							</td>
							@else
								<td> .. </td>
							@endif
						@endforeach

					</tr>

					@endforeach
				</tbody>
			</table>
			@if(!count($pbJobs))
				<div class="no-record">No Records Found</div>
			@endif
		</div>
	</div>
</body>
</html>