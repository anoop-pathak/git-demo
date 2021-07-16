<!DOCTYPE html>
<html lang="en-US">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	</head>
	<body>
		<div>
			<table cellspacing="0" style="color: rgb(0, 0, 0); width: 600px; margin: 0 auto;">
				<tbody>
				<tr>
					<td cellspacing="0" cellpadding="0" style="padding:0;">
						<div style="max-height:200px;width:100%;background:#f1f1f1;border-top-left-radius: 5px; border-top-right-radius: 5px;">
							<img src="{{ config('jp.jobprogress_logo') }}" style="padding:15px;border-top-left-radius: 5px; border-top-right-radius: 5px;" height="60" alt="JOBPROGRESS" />
						</div>
					</td>
				</tr>
				<tr>
					<td cellspacing="0" cellpadding="0" style="color: rgb(0, 0, 0); background: #f9f9f9; padding: 20px 15px;">
						<p style="font-family: arial,sans-serif; margin: 0px; padding: 5px 2px;  font-size: 15px; color: rgb(106, 106, 106);">Dear Super Admin,</p>
						<p style="font-family: arial,sans-serif; margin: 0px; padding: 5px 2px; font-size: 15px; color: rgb(106, 106, 106);">{{$company_name}} have been {{$event}}. Company details : </p>
						<table style="color:#000000;font-size:13px;font-family:arial,sans-serif;">
							<tr>
								<td style="vertical-align:top;padding:5px 0;width:150px;font-size: 15px; color: rgb(106, 106, 106);" cellspacing="0" cellpadding="0">Subscriber ID :</td><td style="vertical-align:top;padding:5px 0;font-size: 15px; color: rgb(106, 106, 106);"> {{$company_id}}</td>
							</tr>
							<tr>
								<td style="vertical-align:top;padding:5px 0;width:150px;font-size: 15px; color: rgb(106, 106, 106);" cellspacing="0" cellpadding="0">Subscriber Name :</td><td style="vertical-align:top;padding:5px 0;font-size: 15px; color: rgb(106, 106, 106);"> {{$first_name}} {{$last_name}}</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td cellspacing="0" cellpadding="0" style="background: #f1f1f1; font-family: arial,sans-serif; font-size: 13px; color: #666; border-bottom-left-radius: 5px; border-bottom-right-radius: 5px; padding: 20px 18px;">
						<div style="margin:5px 0;display:inline-block;">
							Copyright &copy; {{ date("Y") }} <a style="color:#666; text-decoration:none" href="http://jobprogress.com">JOBPROGRESS.COM.</a>
						</div>
						<div style="float:right;margin-top:2px;">
							<a href="https://www.facebook.com/jobprogressapp" target="_blank"><img height="20" width="20" alt="Facebook" src="http://www.jobprogress.com/wp-content/themes/jobprogress/images/facebook.png"></a>
							<a href="https://twitter.com/jobprogress" target="_blank"><img height="20" width="20" alt="Twitter" src="http://www.jobprogress.com/wp-content/themes/jobprogress/images/twitter.png"></a>
							<a href="https://plus.google.com/u/0/b/103195463956275135204/103195463956275135204/" target="_blank"><img height="20" width="20" alt="Google Plus" src="http://www.jobprogress.com/wp-content/themes/jobprogress/images/google-plus.png"></a>
							<a href="https://www.linkedin.com/company/jobprogress/" target="_blank"><img height="20" width="20" alt="Linkedin" src="http://www.jobprogress.com/wp-content/themes/jobprogress/images/linkedin.png"></a>
						</div>
					</td>
				</tr>
			</tbody></table>
		</div>
	</body>
</html>
