<html><head>
	<title></title>
	<meta charset="UTF-8">
 	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<style>
	body {
		text-align: center;
	}
		.two-way-sync-msg {
			padding: 35px 30px;
		    border: 1px #e8e8e8 solid;
		    border-radius: 3px;
		    width: 100%;
		    display: inline-block;
		    max-width: 500px;
		    margin: 50px auto 0;
		    font-family: "Roboto", sans-serif;
	        box-shadow: 0 1px 20px 0 rgba(0, 0, 0, 0.1);
		}
		.two-way-sync-msg img {
			width: 50px;
		}
		.two-way-sync-msg p {
			font-size: 16px;
			margin: 0;
			color: #555;
			line-height: 22px;
		}
		.two-way-sync-msg h3 {
			font-size: 22px;
			margin-bottom: 12px;
		}
	</style>
</head>
<body>
	<div class="two-way-sync-msg">
		<img src="{{config('app.url')}}uploads/extra/ban.png" alt="ban" />
		<h3>Sorry! you cannot connect</h3>
		<p>Please turn off the Google Calendar 2-way syncing to use a common Google Calendar account for multiple JobProgress users.</p>
	</div>

</body></html>