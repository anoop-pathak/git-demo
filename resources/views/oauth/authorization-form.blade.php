<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title>Login</title>
	{{ HTML::style('css/login-form.css') }}
</head>
<body>
	
	<div class="login-container">
			@if($errors->first('0'))
				<div class="alert-msg alert-msg-danger">
					{{  $errors->first('0') }}
				</div>
			@endif
		<h2>Connect to Jobprogress</h2>
		<div class="login-wrapper">
			<div class="login-header">
				<a><img src="https://jobprogress.com/app/img/main-logo-grey.png" alt="JOBPROGRESS"></a>
			</div>
			<div>
				<form method="post" action="{{route('oauth.authorize.post', $params)}}" class="form-horizontal">
					<?php echo Form::token(); ?>
					<input type="hidden" name="client_id" value="{{$params['client_id']}}">
				  	<input type="hidden" name="redirect_uri" value="{{$params['redirect_uri']}}">
				  	<input type="hidden" name="grant_type" value="password">
				  	<input type="hidden" name="client_secret" value="{{$params['client_secret']}}">
				  	<input type="hidden" name="_wpnonce" value="{{$params['_wpnonce']}}">
					<div class="form-group">
						<input autocomplete="off" class="form-control" type="text" name="username" placeholder="Username">
					</div>
					<div class="form-group">
						<input autocomplete="off" class="form-control" type="password" name="password" placeholder="Password">
					</div>
					<div class="form-group">
						<a class="btn btn-grey pull-right" type="submit" name="cancel" href="{{$params['redirect_uri'] ?? ''}}">Cancel</a>
						<button class="btn btn-blue" type="submit" name="submit" value="1">Submit</button>
					</div>
				</form>
			</div>
		</div>
	</div>
</body>
</html>
