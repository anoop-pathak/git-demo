<!DOCTYPE html>
<!-- saved from url=(0040)file:///home/ajay/Desktop/worksheet.html -->
<html class="no-js"><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

	<title> JobProgress </title>
	<link rel="stylesheet" href="https://jobprogress.com/app/#/workcenter">
	<link rel="stylesheet" href="{{config('app.url')}}css/vendor.879fa015.css">
	<link rel="stylesheet" href="{{config('app.url')}}css/main.css"/>
	<link rel="stylesheet" href="{{config('app.url')}}css/worksheet-multi-tier.css"/>
	<meta name="viewport" content="width=device-width">
</head>
<body>
	<div class="container">
		<div style="padding-right: 15px;text-align: right;font-size: 12px;color: #889">
			<span>Last Modified: {{ \Carbon\Carbon::now(Settings::get('TIME_ZONE'))->format(config('jp.date_format'))}}</span>
		</div>
		<div class="jobs-export job-container jp-panel jp-panel-primary">
			<div class="row" style="position: relative;">
				<div class="col-xs-12 text-center" style="margin-bottom: 10px;">
					<h2 class="title" style="word-wrap: unset;">
						<span class="form-title" style="white-space: normal;">
							{{$worksheet_title ?? 'Insurance'}}
						</span>
					</h2>
				</div>

				<div class="col-xs-6">
					<div class="customer-ref">
						<div class="inlineblock margin" style="padding-bottom: 0;">

							@if(!empty($company->logo))
								<span class="img-section">
									<img src="{{ FlySystem::getUrl(config('jp.BASE_PATH').$company->logo)}}" alt="{{ $company->name }}">
								</span>
							@else
								<span class="img-section">
									<img src="{{config('app.url')}}placeholder/placeholder.png"  alt="{{ $company->name }}">
								</span>
							@endif

							<span style="margin-left: 140px;display: block;">
								<span class="company-name">{{ $company->name }}</span><br>
								@if($division && ($address = $division->address))
									<span>
										{!! $address->present()->fullAddress !!}<br>
										@if($division->email)
											{{ $division->email }}<br>
										@endif
										{{ phoneNumberFormat($division->phone, $company_country_code) }}
									</span>
								@else
									<span>
										{!! $company->present()->fullAddress !!}<br>
										{{ $company->office_email }}<br>
										{{ phoneNumberFormat($company->office_phone, $company_country_code) }}
									</span>
								@endif
							</span>
						</div>
					</div>
				</div>
				<div class="col-xs-6" align="right" style="position: static;">
					<div class="sec-job-info">
						<span style="font-size: 20px;line-height: normal;">
							For: {{ $customer->full_name }}
						</span>
					</div>
					<div class="customer-info-sec">

						<div class="sec-job-info">
							<span>{{ $customer->company_name }}</span>
						</div>


						@if(($job->address) && ($address = $job->address->present()->fullAddress))
							<div class="sec-job-info">
								<span>Job Address: {!! $address !!}</span>
							</div>
						@endif


						<div class="sec-job-info">
							<span>{{ $customer->email }}</span>
						</div>

						<div class="sec-job-info">
							<span>
								<?php $phone = $customer->phones()->first(); ?>

								{{ phoneNumberFormat($phone->number, $company_country_code) }}

								@if($phone->ext)
									EXT - {{ $phone->ext }}
								@endif
							</span>
						</div>
					</div>
				</div>
			</div><br>
			<div class="clearfix"></div>
			<div id="financial-section-job">
				<div class="financial-inner-section ng-scope" ng-if="previewMode">
					<div ng-include="&#39;views/includes/financial/sheet/section-preview.html&#39;" class="ng-scope">
						<div class="grid-section">

							<div class="grid-col">
								<label>Job Id</label>
								<span>{{$job->number}}</span>
							</div>
							@if($job->name)
								<div class="grid-col" style="white-space: nowrap;">
									<label>Job Name</label>
									<span>{{$job->name}}</span>
								</div>
							@endif
							@if($job->alt_id)
							<div class="grid-col" style="white-space: nowrap;">
								<label>Job #</label>
								<span> {{$job->full_alt_id}}</span>
							</div>
							@endif
							<div class="grid-col" style="white-space: nowrap;">
								<label>Estimate #</label>
								<span> XXXX</span>
							</div>
							<div class="grid-col" style="white-space: nowrap;">
								<label>Estimate Date </label>
								<span> {{ \Carbon\Carbon::now(Settings::get('TIME_ZONE'))->format(config('jp.date_format'))}}</span>
							</div>

							@if($claim_number)
							<div class="grid-col" style="white-space: nowrap;">
								<label>Claim Number</label>
								<span>{{ $claim_number }}</span>
							</div>

							@endif

							@if($policy_number)
							<div class="grid-col" style="white-space: nowrap;">
								<label>Policy Number</label>
								<span>{{ $policy_number }}</span>
							</div>
							@endif
						</div>

						<!-- show customer rep details -->
						@if(($customerRep = $customer->rep))
							<?php
							$customerRepProfile = $customerRep->profile;
							$customerRepPhone = count($customerRepProfile->additional_phone) ? $customerRepProfile->additional_phone[0] : null;
							?>
							<div class="customer-ref" style="margin-bottom: 20px; margin-top: 10px; width:100%;">
								<span style="display: block;">
									<span class="company-name" style="font-size: 16px; font-weight: bold; display: block;">Salesman/Customer Rep</span>
										<div>{{ $customerRep->full_name }}</div>
										<div>{{ $customerRep->email }}</div>
									@if(isset($customerRepPhone->phone))
										<div>{{ phoneNumberFormat($customerRepPhone->phone, config('company_country_code')) }}</div>
									@endif
								</span>
							</div>
						@endif

						<div class="phase-structure">
							<div class="phase-head">
								@include('partials.xactimate_financial_details_columns')
							</div>

							<div class="phase-body">
								<?php
									$financial_details = \Illuminate\Support\Collection::make($financial_details);
								?>

								@include('partials.xactimate_preview_financial_details', ['financial_details'=> $financial_details])
							</div>
						</div>

						<dir class="clearfix" style="margin:0"></dir>
					</div>
				</div>
			</div>
			<div>
				@if($note)
					<label>Notes:</label>
					<span class="description"><br>{{ $note }}</span>
				@endif
			</div>
			<div class="clearfix"></div>
		</div>
	</div>
</body></html>