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
		<?php
			$updatedDate = new Carbon\Carbon($worksheet->updated_at, Settings::get('TIME_ZONE'));
			$createdDate = new Carbon\Carbon($estimate->created_at, Settings::get('TIME_ZONE'));
		?>
		<div style="padding-right: 15px;text-align: right;font-size: 12px;color: #889">
			<span>Last Modified: {{ $updatedDate->format(config('jp.date_format'))}}</span>
		</div>
		<div class="jobs-export job-container jp-panel jp-panel-primary">
			<div class="row" style="position: relative;">
				<div class="col-xs-12 text-center" style="margin-bottom: 10px;">
					<h2 class="title" style="word-wrap: unset;">
						<span class="form-title" style="white-space: normal;">
							@if(ine($worksheet, 'title'))
								{{$worksheet->title}}
							@else
								Insurance
							@endif
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
								@if(($division = $worksheet->division) && ($divAddress = $division->address))
									<span>
										{!! $divAddress->present()->fullAddress !!}<br>
										@if($division->email)
											{{ $division->email }}<br>
										@endif
										{{ phoneNumberFormat($division->phone, $company_country_code) }}
									</span>
								@else
									<span>
										{!! $company->present()->fullAddress !!}<br>{{ $company->office_email }}<br>{{ phoneNumberFormat($company->office_phone, $company_country_code) }}
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

							<?php
								$estimateSetting = Settings::get('ESTIMATE_WORKSHEET');

								$header  = isset($estimateSetting['header']) ? $estimateSetting['header'] : [];
							?>

							@include('partials.estimate_worksheet_header', ['job'=> $job, 'estimate' => $estimate, 'header' => $header])

							<?php
								$insuranceMeta = null;

								if ($worksheet->insurance_meta) {
									$insuranceMeta  = $worksheet->insurance_meta;
								}

							?>

							@if($insuranceMeta && $insuranceMeta->claim_number)
								<div class="grid-col">
									<label>Claim Number</label>
									<span>{{ $insuranceMeta->claim_number }}</span>
								</div>
								<div class="grid-col">
									<label>Policy Number</label>
									<span>{{ $insuranceMeta->policy_number }}</span>
								</div>
							@endif
						</div>

						<!-- show customer rep details -->
						@if(($customerRep = $customer->rep))
							<?php
							$customerRepProfile = $customerRep->profile;
							$customerRepPhone = count($customerRepProfile->additional_phone) ? $customerRepProfile->additional_phone[0] : null;
							?>
							@if(array_key_exists('customer_rep_name', $header) && isTrue($header['customer_rep_name'])
								|| array_key_exists('customer_rep_email', $header) && isTrue($header['customer_rep_email'])
								|| (array_key_exists('customer_rep_phone', $header) && isTrue($header['customer_rep_phone'])
									&& isset($customerRepPhone->phone)))
								<div class="customer-ref" style="margin-bottom: 20px; margin-top: 10px; width:100%;">
									<span style="display: block;">
										<span class="company-name" style="font-size: 16px; font-weight: bold; display: block;">Salesman/Customer Rep</span>
										@if(array_key_exists('customer_rep_name', $header) && isTrue($header['customer_rep_name']))
											<div>{{ $customerRep->full_name }}</div>
										@endif
										@if(array_key_exists('customer_rep_email', $header) && isTrue($header['customer_rep_email']))
											<div>{{ $customerRep->email }}</div>
										@endif
										@if(array_key_exists('customer_rep_phone', $header) && isTrue($header['customer_rep_phone']) && isset($customerRepPhone->phone))
											<div>{{ phoneNumberFormat($customerRepPhone->phone, config('company_country_code')) }}</div>
										@endif
									</span>
								</div>
							@endif
						@endif

						<div class="phase-structure">
							<div class="phase-head">
								@include('partials.xactimate_financial_details_columns')
							</div>

							<div class="phase-body">
								@include('partials.xactimate_financial_details', ['financial_details'=> $financial_details, 'worksheet' => $worksheet])

								@if($insuranceMeta)
									@include('partials.xactimate_calculation_summary', ['insuranceMeta' => $insuranceMeta])
								@endif

							</div>
						</div>

						<dir class="clearfix" style="margin:0"></dir>
					</div>
				</div>
				<div>
					@if($worksheet->note)
						<label>Notes:</label>
						<span class="description"><br>{{ $worksheet->note }}</span>
					@endif
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="clearfix"></div>
		</div>
	</div>
</body></html>