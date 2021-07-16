<!DOCTYPE html>
<html lang="en-US">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	</head>
	<body>
		<div>
			<table cellspacing="0" style="color: rgb(0, 0, 0); width: 600px; margin: auto;">
				<tbody><tr>
					<td cellspacing="0" cellpadding="0" style="padding:0;"><div style="max-height:200px;width:100%;background:#f1f1f1;border-top-left-radius: 5px; border-top-right-radius: 5px;">
						<img src="{{ config('jp.jobprogress_logo') }}" style="padding:15px;border-top-left-radius: 5px; border-top-right-radius: 5px;" height="60" alt="JOBPROGRESS" />
					</td>
				</tr>
				<tr>
					<td cellspacing="0" cellpadding="0" style="color: rgb(0, 0, 0); background: #f9f9f9; padding: 20px 15px;">
						<p style="font-family: arial,sans-serif; margin: 0px; padding: 5px 2px;  font-size: 15px; color: rgb(106, 106, 106);">Dear {{ $first_name }},</p>
						<p style="font-family: arial,sans-serif; margin: 0px; padding: 5px 2px; font-size: 15px; color: rgb(106, 106, 106);">
						{!! $content ?? '' !!}
						<br><br>
						The document is enclosed here for your reference.
						<br><br>
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
