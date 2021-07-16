<!DOCTYPE html>
<html>
<head>
	<html class="no-js" ng-app="jobProgress" ng-controller="AppCtrl"><head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	<title>JobProgress - ClickThru</title>
	<link rel="stylesheet" href="{{ config('app.url') }}css/clickthru.css" />
	<meta name="viewport" content="width=device-width">
</head>
<body>
	<?php	$updatedDate = new Carbon\Carbon($estimate->updated_at, Settings::get('TIME_ZONE')); ?>
	<div class="clickthru-print-container">
		<div class="created-date">
			<span>Date: {{ $updatedDate->format(config('jp.date_format'))}}</span>
		</div>
		<div class="measure-title">
			<h2>ClickThru Estimate</h2>
		</div>
		<div class="measurement-print">
			<div class="company-details font0">
				<div class="left">
					<div class="company-logo">
					@if( ! empty($company->logo) )
						<img src="{{ FlySystem::getUrl(\Config::get('jp.BASE_PATH').$company->logo) }}" />
					@endif
					</div>
					<div class="address-col">
						<h4>{{ $company->name or '' }}</h4>
						<div class="address mb5">{{ $company->office_address or '' }} {{ $company->office_city }}</div>
						<div class="address mb5">{{ $company->state->code or '' }}
								{{ $company->office_zip }}</div>
						<div class="email mb5">{{$company->office_email}}</div>
						<div class="phone mb5">{{phoneNumberFormat($company->office_phone,$company->country->code)}}</div>
					</div>
				</div>
				<div class="right text-right">
					<h4>For: {{$customer->full_name}} </h4>
					<div class="address mb5">
						<div class="job-details">
							{!! $job->present()->jobIdReplaceWithLable !!}
						</div>
					</div>
					@if(($job->address) && ($jobAddress = $job->address->present()->fullAddress) )
					<div class="address mb5">Job Address: {!! $jobAddress !!} </div>
					<div class="email mb5">{{ $customer->email }}</div>
					<div class="phone mb5">
						<?php $phone = $customer->phones()->first(); ?>
						{{ phoneNumberFormat($phone->number, config('company_country_code')) }}
                        @if($phone->ext)
            	            EXT - {!! $phone->ext !!}
                    	@endif
	                </div>
					@endif
				</div>
			</div>
		</div>
		<div class="data-container">
			<div class="data-block roof-size">
				<span class="item-name">Roof Required</span>
				<span class="unit">{{$estimate->roof_size - $estimate->skylight}} sq ft</span>
			</div>
			<div class="data-block">
				<span class="item-name">Manufacturer</span>
				<span class="unit unit-name">{{$manufacturer->name}}</span>
			</div>
			<div class="data-block">
				<span class="item-name">Type</span>
				@if(!empty($type))
				<span class="unit unit-name">{{$type['name']}}</span>
				@endif
			</div>
			@if(!empty($waterproofing))
			<div class="data-block">
				<span class="item-name">Waterproofing</span>
				<span class="unit unit-name">{{$waterproofing['name']}}</span>
			</div>
			@endif
			<div class="data-block">
				<span class="item-name">Level</span>
				<span class="unit unit-name">{{$level['name']}}</span>
			</div>
			@if(!empty($type) && isset($type['layers']))
			<div class="data-block">
				<span class="item-name">Existing Layers</span>
				<span class="unit">{{$type['layers']}}</span>
			</div>
			@endif
			@if(!empty($shingle))
			<div class="data-block">
				<span class="item-name">Shingle Style</span>
				<span class="unit">
					<span class="unit-name">{{$shingle['name']}}</span> ({{ showAmount($shingle['unit_cost'])}} / sq ft)
				</span>
			</div>
			@endif
			@if(!empty($underlayment))
			<div class="data-block">
				<span class="item-name">Underlayment</span>
				<span class="unit">
					<span class="unit-name">{{$underlayment['name']}}</span> ({{showAmount($underlayment['unit_cost'])}} / sq ft)
				</span>
			</div>
			@endif
			@if(!empty($warranty))
			<div class="data-block">
				<span class="item-name">Warranty Type</span>
				<span class="unit unit-name">{{$warranty['name']}}</span>
			</div>
			@endif
			@if(!empty($pitch))
			<div class="data-block">
				<span class="item-name">Pitch</span>
				<span class="unit">{{$pitch['name']}}</span>
			</div>
			@endif
			@if(!empty($accessToHome))
			<div class="data-block">
				<span class="item-name">Access To Home</span>
				<span class="unit unit-name">{{$accessToHome['type']}}</span>
			</div>
			@endif
			@if(!empty($structure))
			<div class="data-block">
				<span class="item-name">Structure</span>
				<span class="unit unit-name">{{$structure['name']}}</span>
			</div>
			@endif
			@if(!empty($complexity))
			<div class="data-block">
				<span class="item-name">Complexity</span>
				<span class="unit">{{$complexity['name']}}</span>
			</div>
			@endif
			@if($chimneyCount = count($chimney))
			<div class="data-block">
				<span class="item-name">Chimneys</span>
				<span class="unit">{{$chimneyCount}}</span>
			</div>
			@endif
			@if(!empty($others))
				@foreach($others as $other)
					<div class="data-block">
						<span class="item-name">{{$other['type']}}</span>
						<span class="unit">{{$other['count']}}</span>
					</div>
				@endforeach
			@endif
			@if($estimate->skylight)
			<div class="data-block">
				<span class="item-name">Skylight</span>
				<span class="unit">{{$estimate->skylight}} sq ft</span>
			</div>
			@endif
			@if(!empty($gutter))
			<div class="data-block">
				<span class="item-name">Gutters</span>
				<?php $gutterType = ($gutter['type'] == 'with_protection') ? 'install new with gutter protections' : $gutter['type'] ?>
				<span class="unit unit-name">{{preg_replace('/_/', ' ',$gutterType)}}</span>
				@if(isset($gutter['total_size']) && $gutter['total_size'])
				<div class="inner-value-block">
					<span class="item-name">Total Linear Feet</span>
					<span class="unit">{{$gutter['total_size']}}</span>
				</div>
				@endif
				@if(isset($gutter['protection_size']) && $gutter['protection_size'])
				<div class="inner-value-block">
					<span class="item-name">Protection Linear Feet</span>
					<span class="unit">{{$gutter['protection_size']}}</span>
				</div>
				@endif
			</div>
			@endif
			@if($estimate->notes)
			<div class="data-block data-block-notes">
				<span class="item-name notes-text">Notes</span>
				<span class="unit notes-text">{{$estimate->notes}}.</span>
			</div>
			@endif
		</div>
		<?php
			$amount = $estimate->amount;
			$adjustableAmount = $estimate->adjustable_amount;
			$totalAmount = ($amount + $adjustableAmount);
		?>
		<div class="amount-estimated amount-block">
			<span class="amount-label">Estimated Amount:</span>
			<span class="amount">{{showAmount($amount)}}</span>
		</div>
		 <div class="amount-adjustable amount-block">
            <span class="amount-label">Adjustable Amount:</span>
            <span class="amount">{{showAmount($adjustableAmount)}}</span>
        </div>
		@if($estimate->adjustable_note)
		<div class="data-block data-block-notes adjustable-note">
			<span class="item-name notes-text">Reason for adjustable amount</span>
			<span class="unit notes-text">{{$estimate->adjustable_note}}.</span>
		</div>
		@endif
		<div class="amount-total amount-block">
            <span class="amount-label">Total Amount:</span>
            <span class="amount">{{showAmount($totalAmount)}}</span>
        </div>
	</div>
</body>
</html>