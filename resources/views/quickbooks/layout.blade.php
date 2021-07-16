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
	<body class="p-5 qb-body" ng-app="jobProgress">
		<div class="" ng-controller="QbPayCtrl as Ctrl">
			@yield('content') 
		</div>
	</body>

	<script type="text/javascript" src="{{config('app.url')}}js/components/angular/angular.min.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/plugins/signature.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/components/angular-bootstrap/ui-bootstrap.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/components/angular-bootstrap/ui-bootstrap-tpls.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/components/angular-loading-bar/build/loading-bar.min.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/components/angular-validation/dist/angular-validation.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/plugins/angular-validation-rules.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/plugins/perfect-scrollbar/js/perfect-scrollbar.jquery.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/plugins/perfect-scrollbar.js"></script>

	<script type="text/javascript" src="{{config('app.url')}}js/app/window.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/plugins/mask.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/app.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/config.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/filters/replace.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/factory/aside.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/service/card-type.js"></script>
	<script type="text/javascript" src="{{config('app.url')}}js/app/controllers/qb-pay.controller.js"></script>
	
</html>
