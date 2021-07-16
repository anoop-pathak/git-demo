<!DOCTYPE HTML>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" type="text/css" href="https://www.jobprogress.com/app/styles/vendor.205a7bed.css">
	<link rel="stylesheet" type="text/css" href="https://www.jobprogress.com/app/styles/main.f122f545.css">
	<link rel="stylesheet" type="text/css" href="style.css">
	<title>JP - Not Found</title>

	<style type="text/css">
		body {
			height: 100vh;
			overflow: hidden;
			background-color: #fff;
			font-family: Roboto;
		}
		.error-page-wrap {
			max-width: 630px;
			height: 100%;
			display: flex;
			align-items: center;
			margin: auto;
			justify-content: center;
		}
		h2 {
			color: #111;
			margin-top: 40px;
			margin-bottom: 18px;
			font-size: 28px;
		}
		p {
			margin-bottom: 36px;
			line-height: 24px;
		}
		.btn {
			padding: 10px 50px;
			font-size: 14px;
		}
	</style>
</head>
<body>
	<div class="container text-center error-page-wrap">
		<div>
			<img src="{{ config('app.url') }}quickbooks/img.png">
			@if(!$connected)
			<h2 class="font-normal">Something went Wrong.</h2>
			<p class="font-normal">{{ $message }}</p>
			<button class="btn-sm btn btn-inverse text-uppercase font-normal" onclick="window.close();">
				Close
			</button>
			@elseif($connected)
			<h1>{{ $message }}</h1>
			<script>
				window.close();
			</script>
			@endif
		</div>
	</div>
</body>
</html>