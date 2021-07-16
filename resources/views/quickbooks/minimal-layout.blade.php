<html>
	
	<head>
		<script src="{{config('app.url')}}js/jquery-1.12.3.min.js"></script>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="stylesheet" type="text/css" href="{{config('app.url')}}css/bootstrap.css">
		<script src="{{config('app.url')}}js/bootstrap.js"></script>
		<!------ Include the above in your HEAD tag ---------->
		<link rel="stylesheet" href="{{config('app.url')}}css/font-awesome.min.css">
		<link rel="stylesheet" href="{{config('app.url')}}qb-pay/css/style.css">
	</head>
	<body class="p-5 qb-body" >
		@yield('content')
	</body>
</html>
