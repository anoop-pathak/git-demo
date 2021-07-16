<!DOCTYPE html>
<html class="no-js" ng-app="jobProgress">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title ng-bind="pageTitle"> JobProgress </title>
	<link rel="stylesheet" href="{{config('app.url')}}css/main.css" />
	<meta name="viewport" content="width=device-width">
	<style type="text/css">
		body {
			background: #fff;
			margin: 0;
			font-family: Helvetica,Arial,sans-serif;
			color:#333;
			font-size: 18px;
		}
		body label{
			color:#333;
		}
		p {
			margin: 0;
		}
		h1,h2,h3,h4,h5,h6 {
			margin: 0;
		}
		.container {
			width: 780px;
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
		/*.header-part {
			display: inline-block;
		}*/
		.header-part .date-format {
			font-size: 14px;
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
		}
		.company-name {
			font-size: 20px;
		    margin-bottom: 0;
	    	margin-top: 15px;
	    	/*font-weight: bold;*/
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
			display: inline-block;
			font-size: 18px;
			margin: 18px 0;
			margin-bottom: 0;
			padding: 0 18px;
			vertical-align: top;
			width: 45%;
		}
		.job-col:first-child {
			border-right: 1px solid #ccc;
		}
		.job-col:last-child {
			border-color: transparent;
		}
		.job-detail-part label {
			width: 150px;
			display: inline-block;
			vertical-align: top;
		}
		.job-detail-part:first-child label {
			width: 145px;
		}
		.job-detail-part:first-child .attendees-list {
			margin-left: 140px;
		}
		.job-detail-part p {
			display: inline-block;
			vertical-align: top;
		}
		.upper-text {
			text-transform: uppercase;
		}
		.desc {
			margin: 0 20px;
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
			text-align: justify;
		}
		.desc p label { 
			font-weight: normal;
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
	    	margin-left: 150px;
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
			padding-left: 110px;
		}
		.job-detail-part span{
			white-space: nowrap;
		}
		.today-date p{
			text-align: right;
			padding-bottom: 3px;
			/*font-weight: bold;*/
		}
		.pull-right {
			float: right;
		}
		.crew-list {
			display: block;
		    vertical-align: middle;
		    margin-bottom: 15px;
		    margin-top: 20px;
		}
		.crew-list .attendees-label {
			font-size: 18px;
			margin-left: 18px;
			margin-right: 5px;
			width: 142px;
		}
		.crew-list .attendees-list {
			margin-left: 165px;
		}
		.description {
			text-align: justify;
			white-space: pre-wrap;
		}
		p,span{
			font-weight: normal;
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
	</style>
</head>
<body>
	<div class="container">
		<div class="jobs-export">
			<div class="header-part">
				@if($company->logo)
				<div class="company-logo">
					<img src="{{ FlySystem::getUrl(config('jp.BASE_PATH').$company->logo) }}" />
				</div>
				@endif
				<h1 style="width:70%;">{{ $company->name ?? '' }}</h1>
				<p class="company-name pull-right" style='text-align: right; margin-top: 0;'>
					Job Schedule<br>
					<span style='font-size:15px;/*font-weight: bold;*/color: #333; display: block;'>
					Current Date: {{ Carbon\Carbon::now(Settings::get('TIME_ZONE'))->format(config('jp.date_format')) }}
					</span>
					@if($schedule->completed_at)
						<span class="complete-label">Completed</span>
					@endif	
				</p>
				<p class="company-name" style="padding-right: 80px;">
					{{ implode(' / ', array_filter( array_merge(
								(array)$schedule->job->customer->full_name,
								$schedule->trades->pluck('name')->toArray(),
								(array)$schedule->job->full_alt_id) ) )
					}}
					{{-- @if($schedule->completed_at)
						<div class="text-right">
	                    	<span class="complete-label">Completed</span>
	                    </div>
					@endif --}}
				</p>
			</div>
			<div class="clearfix"></div>
			<div>
				<div class="jobs-list">
					<div class="jobs-row">
						<div class="job-col job-detail-part">
							@if($secName = $schedule->job->customer->present()->secondaryFullName)
								<div>
									<label class="attendees-label">Customer Name:</label>
									<p class="attendees-list">
										{{ $secName }}
									</p>
								</div>
							@endif
							@if((!$schedule->job->customer->is_commercial) 
								&& $schedule->job->customer->company_name )
								<div>
									<label class="attendees-label">Company:</label>
									<p class="attendees-list">
										{{ $schedule->job->customer->full_name ?? ''}}
									</p>
								</div>
							@endif

							<div>
								<label class="attendees-label">
								@if($schedule->job->isProject())
								Project Id:
								@else
								Job Id:
								@endif
								</label>
								<p class="attendees-list">
									{{ $schedule->job->number ?? ''}}
								</p>
							</div>
							@if($schedule->job->alt_id)
							<div>
								<label class="attendees-label">
									@if($schedule->job->isProject())
									Project #:
									@else
									Job #:
									@endif
								</label>
								<p class="attendees-list">
									{{ $schedule->job->alt_id }}
								</p>
							</div>
							@endif
							@if($schedule->title)
							<div>
								<label class="attendees-label">Title: </label>
								<p class="attendees-list crew-list">
									{{ $schedule->title }}
								</p>
							</div>
							@endif

							@if($trades = implode(', ', $schedule->trades->pluck('name')->toArray() ))
							<div>
								<label class="attendees-label">Trade type: </label>
								<p class="attendees-list crew-list">
									{{ $trades }}
								</p>
							</div>
							@endif
						</div>

						<div class="job-col job-detail-part">
							<div>
								<label class="attendees-label">Recurring: </label>
								<p class="attendees-list">
									{{ $schedule->present()->recurringText  }}
								</p>
							</div>
							<div>
								<label class="attendees-label">Start Date: </label>
								<p class="attendees-list">
								<?php
									$timeZone  = Settings::get('TIME_ZONE');
									$startDate = convertTimezone($schedule->start_date_time, $timeZone);
									$endDate   = convertTimezone($schedule->end_date_time, $timeZone);
									$diffInDays = $startDate->diffInDays($endDate);
								?>
									{{ $startDate->format(config('jp.date_format'))  }}
								</p>
							</div>
							<div>
								<label class="attendees-label">End Date: </label>
								<p class="attendees-list">
									@if($diffInDays)
									{{ $endDate->subDay()->format(config('jp.date_format'))}}
									@else
									{{ $endDate->format(config('jp.date_format'))}}
									@endIf
								</p>
							</div>
							<?php $days = $schedule->present()->manageOffDays ?>
							@if(count($days['off_dates']))
							<div>
								<label class="attendees-label">Off Dates: </label>
								<p class="attendees-list">
									{{ implode(', ', $days['off_dates']) }}
								</p>
							</div>
							@endif
							<div>
								<label class="attendees-label">#Schedule Days: </label>
								<p class="attendees-list">
									@if(ine($days, 'working_days'))
										{{ $days['working_days'] }}
									@else
										1
									@endif
									 Day(s)
								</p>
							</div>
						</div>

						@if($workTypes = implode(', ', $schedule->workTypes->pluck('name')->toArray() ))
						<div class="crew-list">
							<label class="attendees-label">Work Types:
							</label>
							<p class="attendees-list">
								{{ $workTypes }}
							</p>
							<div class="clearfix"></div>
						</div>
						@endif

						<div class="crew-list">
							<label class="attendees-label">Work Crew:
							</label>
							<p class="attendees-list">
								{{ $schedule->present()->jobRepLaborSubAll }}
							</p>
							<div class="clearfix"></div>

						</div>

						@if($wOCount = $schedule->workOrders->count())
							<div class="crew-list">
								<label class="attendees-label">Work Order(s):
								</label>
								<p class="attendees-list">
									{{ $wOCount }}
								</p>
								<div class="clearfix"></div>
							</div>							
						@endif

						@if($mLCount = $schedule->materialLists->count())
							<div class="crew-list">
								<label class="attendees-label">Material List(s):
								</label>
								<p class="attendees-list">
									{{ $mLCount }}
								</p>
								<div class="clearfix"></div>
							</div>
						@endif

						@if($schedule->job->duration_in_seconds)
						<div class="crew-list">
							<label class="attendees-label">
								@if($schedule->job->isProject())
								Project Duration:
								@else
								Job Duration:
								@endif
							</label>
							<p class="attendees-list">{!! $schedule->job->present()->jobDuration !!}</p>
							<div class="clearfix"></div>
						</div>
						@endif
						<div class="crew-list">
							<label class="attendees-label">
							@if($schedule->job->isProject())
								Project Rep / Estimator:
							@else
								Job Rep / Estimator:
							@endif
							</label>
							<p class="attendees-list">{{ $schedule->job->present()->jobEstimators }}</p>
							<div class="clearfix"></div>
						</div>

						@if($address = $schedule->job->address->present()->fullAddress)
						<div class="crew-list">
							<label class="attendees-label">Job Address: 
							</label>
							<p class="attendees-list">
								{!! $address !!}
							</p>
							<div class="clearfix"></div>
						</div>
						@endif

						<div class="crew-list">
							<label class="attendees-label">Customer Contact: 
							</label>
							<p class="attendees-list">
								<?php 
								$contacts = []; 
								$job = $schedule->job;
								$customer = null;
								?>	
								@if($job)
								<?php $customer = $job->customer; ?>
		                        @foreach( $customer->phones as $key => $phone )
		                            <?php $contacts[$key + 1] = ucfirst($phone->label).': '.phoneNumberFormat($phone->number, $company_country_code);
		                            ?>
                                	@if($phone->ext)
								  		<?php $contacts[$key + 1] .= ' EXT: '. $phone->ext; ?>
									@endif
	                        	@endforeach
			                    @endif
								
								{{ implode(', ', $contacts) }}

								@if(count($contacts) && ($customer->email))
									{{ ' / ' }}
								@endif

								@if(($customer) && $customer->email )
									{{ 'Email: '.$customer->email }}
								@endif

							</p>
							<div class="clearfix"></div>
						</div>

						@if( ($schedule->job->job_contact) 
						&& ( ($schedule->job->job_contact->phone) 
						|| ($schedule->job->job_contact->email)
						|| ($schedule->job->job_contact->additional_phones)
						))
						<div class="crew-list">
							<label class="attendees-label">Job Contact: </label>
							<?php $jobContacts = []; ?>
							<p class="attendees-list">
								@if($schedule->job->job_contact->phone)
									<?php $jobContacts[] = 'Phone: '.phoneNumberFormat($schedule->job->job_contact->phone, $company_country_code); ?>
								@endif

								@if(isset($schedule->job->job_contact->additional_phones))

									<?php $count = count((array)$schedule->job->job_contact->additional_phones) - 1; ?>

		                       		@foreach( $schedule->job->job_contact->additional_phones as $key => $phone )
			                            <?php $jobContacts[$key + 1] = ucfirst($phone->label).': '.phoneNumberFormat($phone->number, $company_country_code);
			                            ?>
		                            	@if(isset($phone->ext) && !empty($phone->ext))
									  		<?php $jobContacts[$key + 1] .= ' EXT: '.$phone->ext; ?>
										@endif

										@if($count == $key && $schedule->job->job_contact->email)
											<?php $jobContacts[$key + 1] .= ' / '; ?>
										@endif
		                        	@endforeach
		                    	@endif

								{{ implode(', ', $jobContacts) }}
	                    		
	                    		@if($schedule->job->job_contact->email)
									{{ 'Email: '.$schedule->job->job_contact->email }}
								@endif

							</p>
							<div class="clearfix"></div>
						</div>
						@endif

						
						@if($schedule->workCrewNotes->count())
						<div class="desc">
							<!-- <h5 style="margin-bottom: 8px;" class="upper-text">Work Crews</h5> -->
							<div class="cust-job-detail work-crew-listing">
								<div class="details-col">
									<label for="appointment-for" class="col-md-4" style="margin-bottom: 10px;font-size: 18px;">
										<strong>Work Crew Notes</strong>
									</label>
									@foreach($schedule->workCrewNotes as $key => $note)
									<div class="row work-crew-row">
										<div class="col-md-12">
											<h4 style="font-size: 16px;" >Note #{{++$key}} </h4>
											<p style="white-space: pre-wrap;">{{$note->note}}</p>
											<!-- ngIf: Ctrl.isAppointment -->
										</div>
									</div>
									@endforeach
								</div>
							</div>
						</div>
						@endif

					</div>
				</div>
				<div class="separator"></div>
			</div>
		</div>
	</div>
</body>
</html>