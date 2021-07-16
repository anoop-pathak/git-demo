<!DOCTYPE html>
<html>
<head>
	<title>Thankyou - Welcome to JobProgress</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href="https://fonts.googleapis.com/css?family=Roboto:300,400&display=swap" rel="stylesheet">
	<style>
		body {
			margin: 0 auto;
			background-image: url("{{ config('app.url') }}/user_invitation_images/signup-bg.jpg");
			background-size: cover;
			background-position: center;
			background-repeat: no-repeat;
			font-family: 'Roboto', sans-serif;
		}
		.main-wrapper {
			position: relative;
			z-index: 1;
			display: flex;
			align-items: center;
			justify-content: center;
			height: 100vh;
			color: #fff;
		    font-size: 16px;
		    padding: 0 20px;
		    overflow: auto;
		}
		.main-wrapper:before {
			position: absolute;
			content: "";
			top: 0;
			bottom: 0;
			left: 0;
			right: 0;
			z-index: 1;
			background-color: rgba(41,111,172,0.4);
		}
		.thankyou-wrapper {
			position: relative;
			z-index: 1;
			background-color: #418BCA;
			background-image: linear-gradient(to top right, #415FCA , #418BCA);
			border-radius: 14px;
			display: flex;
			justify-content: center;
			align-items: center;
			flex-direction: column;
			padding: 50px 20px;
			height: 70vh;
		}
		.thankyou-text {
			text-align: center;
			color: #E8E9EE;
		}
		.thankyou-text h1 {
			margin-bottom: 10px;
			font-weight: 400;
			font-size: 1.8em;
		}
		.thankyou-text p {
			font-size: 1em;
			line-height: 1.5em;
			width: 70%;
			margin: auto;
		}
		.thankyou-text .login-btn {
			display: inline-block;
			text-decoration: none;
			background-color: #fff;
			margin-top: 2em;
			color: #418BCA;
			font-size: 1em;
			text-transform: uppercase;
			border-radius: 4px;
			box-shadow: unset;
			border: 1px solid #fff;
			min-width: 250px;
			height: 3em;
			line-height: 3em;
			cursor: pointer;
			font-family: 'Roboto', sans-serif;
			transition: 0.1s ease;
		}
		.thankyou-text .login-btn:hover {
			background-color: transparent;
			color: #fff;
		}
		.statue-container {
			position: relative;
			width: 100%;
			height: 50%;
			height: 50vh;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			text-align: center;
			padding-bottom: 5px;
		}
		.img-bg {
		    position: absolute;
		    z-index: 0;
		    top: 50%;
		    left: 50%;
		    height: 65%;
		    transform: translate(-50%, -50%);
		}
		.img-contractor {
			height: 100%;
			z-index: 0;
		}
		.poweredby-logo {
			position: absolute;
			top: 1.8em;
			left: 1.5em;
			height: 40px;
		}
		@media(max-width: 1199px) {
			.thankyou-wrapper {
				height: 65vh;
			}
		}
		@media(min-width: 768px) {
			.thankyou-wrapper {
				width: 670px;
			}
		}
		@media(max-width: 767px) {
			.main-wrapper {
				font-size: 13px;
			}
			.thankyou-wrapper {
				padding-top: 55px;
				padding-bottom: 30px;
			}
			.thankyou-text p {
				width: 100%;
			}
			.statue-container {
				height: 35vh;
			}
			.poweredby-logo {
				top: 1em;
				left: 1.5em;
				height: 30px;
			}
		}
		@media(max-width: 767px) and (orientation: portrait) {
			.main-wrapper {
				flex-direction: column;
			}
		}
		@media(max-height: 600px) and (orientation: landscape) {
			.main-wrapper {
				font-size: 14px;
			}
			.thankyou-wrapper {
				width: 100%;
				height: 75vh;
				padding-top: 30px;
				padding-bottom: 30px;
			}
			.statue-container {
				padding-bottom: 0;
				height: 35vh;
			}
			.thankyou-text .login-btn {
				min-width: 200px;
			}
		}
		/* animation */
		@-webkit-keyframes zoomIn {
			from {
				opacity: 0;
				-webkit-transform: scale3d(0.9, 0.9, 0.9);
				transform: scale3d(0.9, 0.9, 0.9);
			}
			50% {
				opacity: 1;
			}
			100% {
				opacity: 1;
			}
		}
		@keyframes zoomIn {
			from {
				opacity: 0;
				-webkit-transform: scale3d(0.9, 0.9, 0.9);
				transform: scale3d(0.9, 0.9, 0.9);
			}
			50% {
				opacity: 1;
			}
			100% {
				opacity: 1;
			}
		}
		.zoomIn {
			-webkit-animation-name: zoomIn;
			animation-name: zoomIn;
		}
		@-webkit-keyframes fadeIn {
			from {
				opacity: 0;
			}
			to {
				opacity: 1;
			}
		}
		@keyframes fadeIn {
			from {
				opacity: 0;
			}
			to {
				opacity: 1;
			}
		}
		.fadeIn {
			-webkit-animation-name: fadeIn;
			animation-name: fadeIn;
		}
		.animated {
			webkit-animation-duration: 1s;
		    animation-duration: 1s;
		    -webkit-animation-fill-mode: both;
		    animation-fill-mode: both;
		}
		.delay-1 {
			animation-delay: 0.4s;
		}
	</style>
</head>
<body>
	<div class="main-wrapper">
		<div class="thankyou-wrapper">
		<img src="{{ config('app.url') }}user_invitation_images/logo.png" alt="JobProgress" class="poweredby-logo">
			<div class="statue-container">
				<img src="{{ config('app.url') }}user_invitation_images/bg-icon.png" alt="icon" class="img-bg">
				<img src="{{ config('app.url') }}user_invitation_images/statue.png" alt="contractor" class="img-contractor zoomIn animated">
			</div>
			<div class="thankyou-text fadeIn animated delay-1">
				<h1>Welcome to {{ $company->name }}</h1>
				<p>Thank You for accepting the invite. You now have access to {{ $company->name }} in JobProgress.</p>
				<a href="{{ config('jp.login_url') }}" class="login-btn">CLICK HERE TO LOGIN</a>
			</div>
		</div>
	</div>
</body>
</html>