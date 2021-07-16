<!DOCTYPE html>
<html>
<head>
	<title></title>
</head>
<body>
	An error occured on server with following details: <br><br>
 <b>User:</b> {{$username}} <br>
 <b>Exception Date Time(UTC):</b> {{\Carbon\Carbon::now()}} <br>
 <b>Subscription:</b> {{$subscription}} <br>
 <b>Scope:</b> {{$scope}} <br>
 <b>IPs:</b> {{$ips}} <br>
 <b>Request Method:</b> {{$request_method}} <br>
 <b>Request Endpoint:</b> {{$request_path}} <br>
 <b>Client:</b> {{$client}} <br>
 @if($client == 'Mobile')
 	<b>Platform:</b> {{$platform}} <br>
 	<b>App Version:</b> {{$app_version}} <br>
 @endif
 <b>Error Detail:</b> {{$exception}} <br>
 <b>Input</b>: {{json_encode($input, true)}} <br>
</body>
</html>