@extends('quickbooks.layout')

@section('content')

<div class="row">
	<div class="col-lg-12">
		@if($status == 1)
		<div class="alert alert-success">{{ $message }}</div>
		<p>
			Page Will automatically close itself in <strong id="timer">3</strong> seconds
		</p>
		@endif

		@if($status == 0)
		<div class="alert alert-warning">
			{{ $message }}
		</div>
		@endif
	</div>
</div>

@if($status == 1)
<script>
(function() {
	timer = 3

	setInterval(function(){
		document.getElementById('timer').innerText = --timer;
		console.log(timer);
	}, 1000);

	setTimeout(function(){
		if(parent.window) {
			parent.window.close();
		} else {
			window.close();
		}
	}, timer * 1000);

})();
</script>
@endif

@endsection