<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title>Error</title>
	<style type="text/css">
		.error-container {
			max-width: 800px;
			padding: 20px;
			border: 1px solid #ddd;
			font-family: helvetica, arial;
			margin: auto;
			text-align: center;
			margin-top: 150px;
		}
		h1 {
			margin-top: 110px;
			font-size: 25px;
			color: #c81b1b;
			margin-top: 0;
		}
		p {
			font-size: 24px;
		}
	</style>
</head>
<body>
	<div class="error-container">
		<h1>{{ $message['subject'] ?? trans('response.error.error_page.subject') }}</h1>
		<p>{{ $message['content'] ?? trans('response.error.error_page.content') }}</p>
		<p style="display: none;"> {{ $errorDetail ?? '' }} </p>
	</div>
</body>
</html>