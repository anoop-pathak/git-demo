<!DOCTYPE html>
<html class="no-js" ng-app="jobProgress" ng-controller="AppCtrl"><head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<title> JobProgress </title>
<link rel="stylesheet" href="{{config('app.url')}}css/measurement.css" />
<meta name="viewport" content="width=device-width">
<body>
	<p class="last-modified">Last Modified: <?php echo  \Carbon\Carbon::now(Settings::get('TIME_ZONE'))->format(config('jp.date_format')) ; ?></p>
	<div class="measure-title">
		<h2>{{ $measurement->title }}</h2>
	</div>
	<div class="measurement-print">
		<div class="company-details font0">
			<div class="left">
				<?php $logoHide = 'logo-hide-text'; ?>
				@if($company->logo)
					<?php $logoHide = null; ?>
					<div class="company-logo">
						<img src="{{ FlySystem::getUrl(config('jp.BASE_PATH').$company->logo) }}" alt="$company->name"/>
					</div>
				@endif
				<div class="address-col {{ $logoHide }}">
					<h4>{{ $company->name }}</h4>
					<div class="address mb5">{!! $company->present()->fullAddress !!}, </div>
						<div class="email mb5">{!! $company->present()->additionalEmail !!}</div>
					<div class="phone mb5">{{ phoneNumberFormat($company->present()->additionalPhone, $country->code) }}</div>
				</div>
			</div>
			<div class="right text-right">
				<div class="address-col">
					<h4>For: {{ $customer->full_name }} </h4>
					@if($jobAddress = $job->address->present()->fullAddress)
					<div class="address mb5">Job Address: 
						{!! $jobAddress !!}
					</div>
					@endif
					<div class="email mb5">{{ $customer->email }}</div>

					<?php $customerPhone = $customer->phones()->first(); ?>
					<div class="phone mb5">
						{{ phoneNumberFormat($customerPhone->number, $country->code)}}
					</div>
				</div>
			</div>
		</div>
	</div>
	@foreach($measurement->trades as $trade)
	<div class="measurement-data-container">
		<h5>{{ $trade->name }}</h5>
		<div class="data-container">
		@foreach($trade->measurementValues as $value)
			<div class="data-block">
				<span class="item-name">{{ $value->name }}</span>
				<span class="unit">					
					{{ $value->value ?? '--'}}
				</span>
			</div>
		@endforeach
		</div> 
	</div>
	@endforeach
</body>
</html>