<?php
	if ($job->isProject()) {
		$label = 'Project Id';
	} else {
		$label = 'Job Id';
	}
?>
@if(array_key_exists('job_id', $header) && isTrue($header['job_id']))
	<div class="grid-col">
		<label>{{ $label }}</label>
		<span>{{$job->number}}</span>
	</div>
@endif
@if(array_key_exists('job_name', $header) && isTrue($header['job_name']) && $job->name)
	<div class="grid-col" style="white-space: nowrap;">
		<label>Job Name</label>
		<span>{{ $job->name }}</span>
	</div>
@endif
@if(array_key_exists('job_number', $header) && isTrue($header['job_number']) && $job->alt_id)
	<div class="grid-col" style="white-space: nowrap;">
		<label>Job #</label>
		<span> {{$job->full_alt_id}}</span>
	</div>
@endif

@if(array_key_exists('estimate_number', $header) && isTrue($header['estimate_number']))
	<div class="grid-col" style="white-space: nowrap;">
		<label>Estimate #</label>
		<span> {{ $estimate->serial_number}}</span>
	</div>
@endif

@if(array_key_exists('estimate_date', $header) && isTrue($header['estimate_date']))
	<div class="grid-col">
		<label>Estimate Date </label>
		<span> {{$createdDate->format(config('jp.date_format'))}}</span>
	</div>
@endif

@if(array_key_exists('contractor_license_number', $header)
	&& isTrue($header['contractor_license_number'])
	&& ($company = $job->company)
	&& $company->present()->licenseNumber)

	<div class="grid-col">
		<label>Contractor license #</label>
		<span> {{ $company->present()->licenseNumber }}</span>
	</div>
@endif