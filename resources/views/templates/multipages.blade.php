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
<body class="wkhtml-print {{$pageType}} legal-size-proposal a4-size-proposal" style="width:794px;">
	@foreach($pages as $page)
    <div class="page" style="margin:0; page-break-before: always;">
       {!! $page->content !!}
    </div>
	@endforeach
</body>
</html>
