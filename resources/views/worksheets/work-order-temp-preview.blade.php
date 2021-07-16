<!DOCTYPE html>
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
		<div style="padding-right: 15px;text-align: right;font-size: 13px;color: #889">

			<span>Last Modified: {{ \Carbon\Carbon::now(Settings::get('TIME_ZONE'))->format(config('jp.date_format'))}}</span>
		</div>
		<div class="jobs-export job-container jp-panel jp-panel-primary">
			<div class="row" style="position: relative;">
				<div class="col-xs-12 text-center" style="margin-bottom: 10px;">
					<h2 class="title">
						{{$worksheet_title ?? 'Work Order'}}
					</h2>
				</div>

				<div class="col-xs-6">
					<div class="customer-ref">
						<div class="inlineblock margin" style="padding-bottom: 0;">
							@if(!empty($company->logo))
							<span class="img-section"><img src="{{ FlySystem::getUrl(config('jp.BASE_PATH').$company->logo)}}" alt="{{ $company->name }}"></span>
							@else
							<span class="img-section"><img src="{{config('app.url')}}placeholder/placeholder.png"  alt="{{ $company->name }}"></span>
							@endif
							<span style="margin-left: 140px;display: block;">
								<span class="company-name">{{ $company->name }}</span><br>
								<?php
								$address = $company->present()->fullAddress;
								$email   = $company->office_email;
								$phone   = $company->office_phone;

								if($division) {
									if($division->address) {
										$address = $division->address->present()->fullAddress ?: $address;
									}
									$email = $division->email ?: $email;
									$phone = $division->phone ?: $phone;
								}
								?>
								<span>
									{!! $address !!}<br>
									{{ $email }}<br>
									{{ phoneNumberFormat($phone, $company_country_code) }}
								</span>
							</span>
						</div>
					</div>
				</div>
				<div class="col-xs-6" align="right" style="position: static;">
					@if(!$hide_customer_info)
						<div class="sec-job-info">
							<span style="font-size: 20px;line-height: normal;">For: {{ $customer->full_name }}</span>
						</div>
						<div class="customer-info-sec">

							<div class="sec-job-info">
								<span>{{ $customer->company_name }}</span>
							</div>

							@if( ($job->address) &&  ($address = $job->address->present()->fullAddress))
							<div class="sec-job-info">
								<span> <i style="color: #111;font-style: normal;">Job Address: </i>{!! $address !!} </span>
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
					@else
						@if( ($job->address) &&  ($address = $job->address->present()->fullAddress))
							<div class="sec-job-info">
								<span> <i style="color: #111;font-style: normal;">Job Address: </i>{!! $address !!} </span>
							</div>
						@endif
					@endif
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
							<div class="grid-col" style="white-space: nowrap;"><label>Job #</label><span> {{$job->full_alt_id}}</span></div>
							@endif
							<div class="grid-col" style="white-space: nowrap;"><label>Work Order #</label><span> XXXX</span></div>
							<div class="grid-col"><label>Work Order Date </label><span> {{ \Carbon\Carbon::now(Settings::get('TIME_ZONE'))->format(config('jp.date_format'))}}</span></div>				
						</div>
						@if(!$hide_pricing)
						<div class="billed-box" style="float: right;">
							<div class="main-heading">
								<p style="">Work Order Amount</p>
								<span>{{ showAmount($total_amount)}}</span>
							</div>
						</div>
						@endif
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
						<div class="tier-table-body">
							<table class="table tier-table margin0">

								@include('partials.financial_details_columns', [
									'printFields' 	  => $printFields,
									'worksheetMargin' => $margin,
								])

								@include('partials.preview_financial_details', [
									'financial_details' => $financial_details,
									'printFields' 		=> $printFields,
								])

								@include('partials.preview_worksheet_calculation_summary')
							</table>
						</div>
						<div class="clearfix" style="margin:0"></div>
						<br>	
					</div>
				</div>
			</div>
			<div>
				@if($note)
				<label>Notes:</label>
				<span class="description"><br>{{ $note }}</span>
				@endif
			</div>
		</div>
		@if($attachments)
			@include('partials.preview_worksheet_with_attachments', [
			'attachments'  	=> $attachments,
			'company'		=> $company,
			'customer'		=> $customer,
			'job'			=> $job,
			'preview'		=> true,
			'division'		=> $division
			])
		@endif
	</div>


	</body></html>