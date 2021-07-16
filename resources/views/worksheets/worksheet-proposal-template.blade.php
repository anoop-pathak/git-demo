<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title>Download</title>
	<link rel="stylesheet" href="{{config('app.url')}}css/template-preview.css" />
	<style type="text/css">
		body .dropzone-container {
		 box-shadow: none;
		}
	</style>
</head>
<body class="wkhtml-print {{$pageType}}">
    <div class="page" style="margin:0; page-break-before: always;">
       {!! $content !!}
    </div>
</body>
</html>
