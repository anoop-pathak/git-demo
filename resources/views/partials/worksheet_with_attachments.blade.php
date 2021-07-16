@foreach($attachments->chunk($attachments_per_page) as $chunk)
<div class="page wkhtml-print attachment-container" style="text-align:center;position:relative;margin:0.9cm;">
	<div style="text-align:left">
		<div class="company-detail-info-print">
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
			<label class="company-name"><b>{{$company->name}}</b></label>
			<label>{!! $address !!}</label>
			<label>{{ $email }}</label>
			<label>{{ phoneNumberFormat($phone, $company_country_code) }}</label>
		</div>
		<div class="customer-detail-info-print">
			@if(!$worksheet->hide_customer_info)
				<label>{{$customer->full_name}}/{{$job->number}}</label>
			@endif

			@if( ($job->address) &&  ($address = $job->address->present()->fullAddress))
				<label>{!! $address !!}</label>
			@endif
		</div>
	</div>
	<div style="clear:both"></div>
	<h2>Attached Images</h2>
	@foreach($chunk as $attachment)
	<div class="attach-images">
		<p>{{$attachment->name}}</p>
		@if($preview)
		<!-- attachment file path for worksheet preview -->
		<img style="max-width: 100%; max-height: 360px;" src="{{FlySystem::getUrl($attachment->path)}}">
		@else
		<!-- attachment file path for worksheet pdf file -->
		<img style="max-width: 100%; max-height: 360px;" src="{{FlySystem::getUrl(config('jp.BASE_PATH').$attachment->path)}}">
		@endif
	</div>
	@endforeach	
</div>
@endforeach