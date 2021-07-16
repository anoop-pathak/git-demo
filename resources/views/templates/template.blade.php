<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title>Download</title>
	<link rel="stylesheet" href="{{ asset('css/template-preview.css') }}"/>
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
<body class="wkhtml-print {{$pageType}}" style='width:794px;'>
    <div class="page" style="margin:0; page-break-before: always;">
       {!! $content !!}
    </div>
</body>
</html>
