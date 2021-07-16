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
			/*width: 780px;*/
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
			margin-left: 145px;
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
		.work-crew-row {
			margin-bottom:10px;
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
				<h1 style="width:56%;">{{ $company->name ?? '' }}</h1>
				<p class="company-name pull-right" style='text-align: right;'>
					Job Work Crew Notes<br>
					<span style='font-size:15px;/*font-weight: bold;*/color: #333;'>
					Current Date: {{ Carbon\Carbon::now(Settings::get('TIME_ZONE'))->format(config('jp.date_format')) }}
					</span>
				</p>
			</div>
			<div class="clearfix"></div>
			<div>
				<div class="jobs-list">
					<div class="jobs-row">
						<div class="job-col job-detail-part">
							<div>
							@if($job->customer->is_commercial)
								<label class="attendees-label">Company:</label>
							@else
								<label class="attendees-label">Customer Name:</label>
							@endif
								<p class="attendees-list">
									{{ $job->customer->full_name ?? ''}}
								</p>
							</div>

							<div>
								<label class="attendees-label">
								@if($job->isProject())
								Project Id:
								@else
								Job Id:
								@endif
								</label>
								<p class="attendees-list">
									{{ $job->number ?? ''}}
								</p>
							</div>
							@if($job->alt_id)
							<div>
								<label class="attendees-label">
									@if($job->isProject())
									Project #:
									@else
									Job #:
									@endif
								</label>
								<p class="attendees-list">
									{{ $job->full_alt_id }}
								</p>
							</div>
							@endif

							@if(($job->address) && ($address = $job->address->present()->fullAddress))
								<div>
									<label class="attendees-label">Job Address: 
									</label>
									<p class="attendees-list">
										{!! $address !!}
									</p>
								</div>
							@endif

							@if($job->duration_in_seconds)
								<div>
									<label class="attendees-label">
										@if($job->isProject())
										Project Duration:
										@else
										Job Duration:
										@endif
									</label>
									<p class="attendees-list">{!! $job->present()->jobDuration !!}</p>
								</div>
							@endif

							<div>
								<label class="attendees-label">
								@if($job->isProject())
									Project Rep / Estimator:
								@else
									Job Rep / Estimator:
								@endif
								</label>
								<p class="attendees-list">{{ $job->present()->jobEstimators }}</p>
							</div>
						</div>

						<div class="job-col job-detail-part">
							@if($trades = implode(', ', $job->trades->pluck('name')->toArray() ))
							<div>
								<label class="attendees-label">Trade type: </label>
								<p class="attendees-list">
									{{ $trades }}
								</p>
							</div>
							@endif

							@if($workTypes = implode(', ', $job->workTypes->pluck('name')->toArray() ))
								<div>
									<label class="attendees-label">Work Types:
									</label>
									<p class="attendees-list">
										{{ $workTypes }}
									</p>
								</div>
							@endif
						</div>

						<div class="crew-list">
							<label class="attendees-label">Work Crew:
							</label>
							<p class="attendees-list">
								{{ $job->present()->jobRepLaborSubAll }}
							</p>
							<div class="clearfix"></div>

						</div>

						<div class="crew-list">
							<label class="attendees-label">Customer Contact: 
							</label>
							<p class="attendees-list">
							<?php $contacts = []; ?>
								@if($job->customer->phones->count())
			                    
			                        @foreach( $job->customer->phones as $key => $phone )
			                            <?php $contacts[$key + 1] = ucfirst($phone->label).': '.phoneNumberFormat($phone->number, $company_country_code);
			                            ?>
	                                	@if(isset($phone->ext) && !empty($phone->ext))
									  		<?php $contacts[$key + 1] .= ' EXT: '. $phone->ext; ?>
										@endif

		                        	@endforeach
			                    @endif
								{{ implode(', ', $contacts) }}

								@if(count($contacts) && $job->customer->email)
									{{ ' / ' }}
								@endif

								@if($job->customer->email )
									{{ 'Email: '.$job->customer->email }}
								@endif

							</p>
							<div class="clearfix"></div>
						</div>

						@if( ($job->job_contact) 
						&& ( ($job->job_contact->phone) 
						|| ($job->job_contact->email)
						|| ($job->job_contact->additional_phones)
						))
						<div class="crew-list">
							<label class="attendees-label">Job Contact: </label>
							<?php $jobContacts = []; ?>
							<p class="attendees-list">
								@if($job->job_contact->phone)
									<?php $jobContacts[] = 'Phone: '.phoneNumberFormat($job->job_contact->phone, $company_country_code); ?>
								@endif

								@if(isset($job->job_contact->additional_phones))

									<?php $count = count((array)$job->job_contact->additional_phones) - 1; ?>

		                       		@foreach( $job->job_contact->additional_phones as $key => $phone )
			                            <?php $jobContacts[$key + 1] = ucfirst($phone->label).': '.phoneNumberFormat($phone->number, $company_country_code);
			                            ?>
		                            	@if(isset($phone->ext) && !empty($phone->ext))
									  		<?php $jobContacts[$key + 1] .= ' EXT: '.$phone->ext; ?>
										@endif

										@if($count == $key && $job->job_contact->email)
											<?php $jobContacts[$key + 1] .= ' / '; ?>
										@endif
		                        	@endforeach
		                    	@endif

								{{ implode(', ', $jobContacts) }}
	                    		
	                    		@if($job->job_contact->email)
									{{ 'Email: '.$job->job_contact->email}}
								@endif

							</p>
							<div class="clearfix"></div>
						</div>
						@endif
						@if($work_crew_notes->count())
						<div class="desc">
							<!-- <h5 style="margin-bottom: 8px;" class="upper-text">Work Crews</h5> -->
							<div class="cust-job-detail work-crew-listing">
								<div class="details-col">
									<label for="appointment-for" class="col-md-4" style="margin-bottom: 10px;font-size: 18px;">
										<strong>Work Crew Notes</strong>
									</label>
									@foreach($work_crew_notes as $key => $note)
									<div class="row work-crew-row">
										<div class="col-md-12">
											<h4 style="font-size: 16px;" >Note #{{++$key}} </h4>
											<p style="white-space: pre-wrap;">{{$note->note}}</p>
											<!-- ngIf: Ctrl.isAppointment -->
											@if(!empty($workCrew = $note->present()->workCrew))
											<div class="flag-container" style="margin:10px 0">
												<p class="flag" style="border-color:#B284BE;margin-right:8px;">
												{{ $workCrew }}
												</p>
											</div>
											@endif
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