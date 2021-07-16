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
		<div style="padding-right: 15px;text-align: right;font-size: 13px;color: #889;">
			<span>Last Modified: {{ \Carbon\Carbon::now(Settings::get('TIME_ZONE'))->format(config('jp.date_format'))}}</span>
		</div>
		<div class="jobs-export job-container jp-panel jp-panel-primary">
			<div class="row" style="position: relative;">
				<div class="col-xs-12 text-center" style="margin-bottom: 10px;">
					<h2 class="title">{{$worksheet_title ?? 'Form / Proposals'}}</h2>
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
							@include('partials.proposal_worksheet_preview_header', [
								'job'      => $job,
								'header'   => $header,
								'tax_rate' => $tax_rate,
								'overhead' => $overhead,
								'profit'   => $profit,
								'company'  => $company,
							])
						</div>
						<div class="billed-box" style="
						    float: right;
						">
							<div class="main-heading">
								<p style="">Proposal Amount</p>
								<span>{{ showAmount($total_amount)}}</span>
							</div>
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
						@if($branch)
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
						<dir class="clearfix" style="margin:0"></dir>
						<br>
					</div>
				</div>
			</div>
			@if($note)
			<div>
				<label>Notes:</label>
				<span class="description"><br>{{ $note }}</span>
			</div>
			@endif
			<div class="clearfix"></div> <br>
			@if(ine($footer, 'customer_rep_signature') 
				&& (($customer->rep) && ($rep = $customer->rep->signature)))
			<div class="pull-left">
				<label style="vertical-align: top">Customer Rep Signature: </label><br>
				<div class="sign section-block"><div class="jp-border jp-signature" style="width:230px;height:80px"> <img src="{{ $rep->signature }}" class="sign-temp ng-isolate-scope" path="img/sign.png"> <div class="sign-date">{{ \Carbon\Carbon::now(Settings::get('TIME_ZONE'))->format(config('jp.date_format'))}}</div> </div></div>
			</div>
			@endif
			@if(ine($footer, 'customer_signature'))

				@if($signature)
				<div class="pull-right">
					<label style="vertical-align: top">Customer Signature: </label>
					<div class="sign section-block"><div class="jp-border jp-signature" style="width:230px;height:80px"> <img src="{{ $signature }}" class="sign-temp ng-isolate-scope" path="img/sign.png"> <div class="sign-date">{{ \Carbon\Carbon::now(Settings::get('TIME_ZONE'))->format(config('jp.date_format'))}}</</div> </div></div>
				</div>
				@else
				<div class="pull-right">
					<label style="vertical-align: top">Customer Signature:</label><br> 
					<div class="sign section-block"><div class="jp-border jp-signature" style="width:230px;height:80px"> <img src="{{ config('app.url')}}/placeholder/sign.png" class="sign-temp ng-isolate-scope" path="img/sign.png"> <div class="sign-date">Signature Date</div> </div></div>
				</div>
				@endif
				<div style="clear: both;"></div>
			@endif
		</div>
		@if($attachments)
			@include('partials.preview_worksheet_with_attachments', [
			'attachments'  	=> $attachments,
			'company'		=> $company,
			'customer'		=> $customer,
			'job'			=> $job,
			'preview'		=> true,
			'division'      => $division
			])
		@endif
	</div>


	</body></html>