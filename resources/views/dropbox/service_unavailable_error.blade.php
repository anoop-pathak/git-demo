<!DOCTYPE HTML>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>KM - Not Found</title>
		<style>
			* {
				margin: 0 auto;
				box-sizing: border-box;
			}
			body {
				overflow: hidden;
				margin: 0 auto;	
				height: 100vh;
				background-color: #fff;
			}
			.not-found-wrap {
				display: flex;
				height: 100%;
				align-items: center;
				justify-content: space-around;
				width: 580px;
				margin: auto;
			}
			.wrap-404 {
				position: relative;
				height: 200px;
			    width: 200px;
		        flex: 200px 0 0;
			    background: #ddd;
			    color: #999;
			    text-align: center;
			    border-radius: 100%;
			}
			.text-404 {
			    position: absolute;
			    top: 50%;
			    left: 0;
			    right: 0;
			    transform: translateY(-50%);
			}
			.text-404 h2 {
				font-size: 85px;
				line-height: 75px;
			}
			.text-404 p {
				width: 90%;
			}
			.text-not-found h2 {
				color: #c81b1b;
				font-size: 60px;
				margin-bottom: 10px;
			}
			.text-not-found {
				flex: auto;
				padding-left: 30px;
			}
			.text-not-found p {
				margin-bottom: 0;
				color: #777;
				font-size: 20px;
			}
			.text-not-found a {
				color: #c81b1b;
				font-size: 16px;
			}
			@media(max-width: 767px) {
				.main-logo-holder {
					padding: 30px 0;
				}
				.main-logo-holder img {
					height: 45px;
				}
				.not-found-wrap {
					width: 100%;
					padding-top: 0;
					flex-direction: column;
				}
				.text-not-found {
					padding-top: 15px;
					text-align: center;
				}
				.text-not-found h2 {
					font-size: 50px;
				}
				.wrap-404 {
					width: 160px;
					height: 160px;
				}
				.text-404 h2 {
					font-size: 70px;
				}
			}
		</style>
	</head>
	<body>
		<div class="not-found-wrap">
			<!-- <div class="wrap-404">
				<div class="text-404">
					<h2>503</h2>
					<p>Dropbox Service Unavailable</p>
				</div>
			</div> -->
			<div class="text-not-found">
				<h2>Oops!</h2>
				<p>We are unable to connect to Dropbox currently. Please try after some time!</p>
			</div>
		</div>
	</body>
</html>