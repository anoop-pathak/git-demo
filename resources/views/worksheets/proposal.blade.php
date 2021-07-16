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
	<?php	$updatedDate = new Carbon\Carbon($worksheet->updated_at, Settings::get('TIME_ZONE')); ?>
	<div class="container">
		<div style="padding-right: 15px;text-align: right;font-size: 13px;color: #889">
			<span>Last Modified: {{ $updatedDate->format(config('jp.date_format'))}}</span>
		</div>
		<div class="jobs-export job-container jp-panel jp-panel-primary">
			<div class="row" style="position: relative;">
				<div class="col-xs-12 text-center" style="margin-bottom: 10px;">
					<h2 class="title">
					@if(ine($worksheet, 'title'))
						{{$worksheet->title}}
					@else
						Form / Proposal
					@endif
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
								<?php
									$address = $company->present()->fullAddress;
									$email   = $company->office_email;
									$phone   = $company->office_phone;

									if($division = $worksheet->division) {
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
					<div class="sec-job-info">
						<span style="font-size: 20px;line-height: normal;">For: {{ $customer->full_name }}</span>
					</div>
					<div class="customer-info-sec">

						<div class="sec-job-info">
							<span>{{ $customer->company_name }}</span>
						</div>

						@if(($job->address) && ($address = $job->address->present()->fullAddress))
						<div class="sec-job-info">
							<span>Job Address: {!! $address !!} </span>
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
							$proposalSetting = Settings::get('PROPOSAL_WORKSHEET');
							$header = $proposalSetting['header'];
							$footer = $proposalSetting['footer'];
						?>
							@include('partials.proposal_worksheet_header', ['job'=> $job, 'proposal' => $proposal, 'header' => $header])
						</div>
						<div class="billed-box" style="
						    float: right;
						">
							<div class="main-heading">
								<p style="">Proposal Amount</p>

								<span>{{ showAmount($worksheet->calculateTotal()) }}
							</div>
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

						<!-- SRS branch name -->
						@if(($branch = $worksheet->branch))
							<div class="branch-section">
								<div class="branch-name">
									<label>SRS Branch: </label>
									<span>{{ $branch->name }} (Code: {{ $branch->branch_code }})</span>
								</div>
							</div>
						@endif
						<div class="tier-table-body">
							<table class="table tier-table margin0">
								@include('partials.financial_details_columns', [
									'printFields' 		=> $printFields,
									'worksheetMargin'	=> $worksheet->margin,
								])

								@include('partials.financial_details',[
									'financial_details' => $financial_details,
									'worksheet' 		=> $worksheet,
									'printFields'		=> $printFields
								])
								
								@include('partials.worksheet_calculation_summary', ['worksheet' => $worksheet])
							</table>
						</div>

						@if(!$worksheet->tax_rate)
						<br>
					 	@endif
						<div class="clearfix" style="margin:0"></div>
					</div>
				</div>
			</div>
			<div>
				@if($worksheet->note)
				<label>Notes:</label>
				<span class="description"><br>{{ $worksheet->note }}</span>
				@endif
			</div>
			<div class="clearfix"></div> <br>
			@include('partials.proposal_worksheet_footer', ['customer'=> $customer, 'proposal' => $proposal, 'footer' => $footer])
			<div style="clear: both;" ></div>
		</div>
		@if(!$attachments->isEmpty())
			@include('partials.worksheet_with_attachments', [
			'attachments'   		=> $attachments,
			'attachments_per_page'	=> $attachments_per_page,
			'company'				=> $company,
			'customer'				=> $customer,
			'job'					=> $job,
			'preview'				=> false,
			'worksheet'				=> $worksheet
			]);
		@endif
	</div>				


	</body></html>