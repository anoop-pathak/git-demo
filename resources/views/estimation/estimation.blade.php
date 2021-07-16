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
		body {
		 background: none;
		 margin: 0;
		 font-size: 0;
		}
	</style>
</head>
<body class="wkhtml-print {{$pageType}} legal-size-estimate a4-size-estimate" style="position:relative;width:794px;">
	{!! $page->template !!}
	<div style="position:absolute;top:0;left:0;right:0;bottom:0;">
		<img src="{{$page->template_cover}}">
	</div>
</body>
</html>
