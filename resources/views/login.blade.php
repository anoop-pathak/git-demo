<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title></title>
</head>
<body>
	<form method="post" action="{{ action('SessionController@start') }}" accept-charset="UTF-8">
		<input type="hidden" value="password" name='grant_type'/>
		<input type="text" value="testdata164@gmail.com" name='username'>
		<input type="text" value="dummy123" name='password'>
		<input type="hidden" value="12345" name='client_id'>
		<input type="hidden" value="XraqRySfIhUTuvdfz7ATuJxXYf8aX5MY" name='client_secret'>
		<input type='submit' value="submit">
	</form>
</body>
</html>