<!DOCTYPE html>
<html lang="en-US">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	</head>
	<body>
		<div>
			<table cellspacing="0" style="color: rgb(0, 0, 0); width: 600px; margin: auto;">
				<tbody><tr>
					<td cellspacing="0" cellpadding="0" style="padding:0;">
						<div style="background:#f1f1f1;padding: 15px;">
							<a href="{{Settings::get('WEBSITE_LINK')}}" style="color: inherit;">
							@if(! empty ($company->logo) )
								<img src="{{ FlySystem::getUrl(config('jp.BASE_PATH').$company->logo, false) }}" height="50" style="display: inline-block;vertical-align: middle;margin-right: 10px;" />@endif<h3 style="display: inline-block;vertical-align: middle;margin: 0;font-size: 20px;">{{ $company->name }}</h3>
							</a>
						</div>
					</td>
				</tr>
				<tr>
					<td cellspacing="0" cellpadding="0" style="color: rgb(0, 0, 0); background: #f9f9f9; padding: 20px 15px;">
						<p style="font-family: arial,sans-serif; margin: 0px; padding: 5px 2px;  font-size: 15px; color: rgb(106, 106, 106);">Dear {{$user['first_name']}} {{$user['last_name']}},</p>
						<p style="font-family: arial,sans-serif; margin: 0px; padding: 5px 2px; font-size: 15px; color: rgb(106, 106, 106);">
						{!! $content !!} <br>
							@if($proposal->comment)
								<?php $label = 'Reason'; ?>
								@if($proposal->status == App\Models\Proposal::ACCEPTED)
								<?php $label = 'Customer Comment'; ?>
								@endif
							<br><b>{{ $label }}</b> - {{ $proposal->comment }}<br>
							@endif
						</p>
							<p style="font-family: arial,sans-serif; margin: 0px; padding: 5px 2px; font-size: 15px; color: rgb(106, 106, 106);">
							Thanks<br>
							JobProgress Support
						</p>
					</td>
				</tr>
				<tr>
					<td cellspacing="0" cellpadding="0" style="background: #f1f1f1; font-family: arial,sans-serif; font-size: 13px; color: #666; border-bottom-left-radius: 5px; border-bottom-right-radius: 5px; padding: 20px 18px;">
						<div style="margin:5px 0;display:inline-block;">
							Copyright &copy; {{ date("Y") }} <a style="color:#666; text-decoration:none" href="http://jobprogress.com">JOBPROGRESS.COM.</a>
						</div>
					</td>
				</tr>
			</tbody></table>
		</div>
	</body>
</html>
