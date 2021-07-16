<div style="margin:10px 3px -2px 0"  class="resource-viewer {{$proposal->status}}-border">
	<div class="resource-inner {{$proposal->status}}">
		<span class="web-update-icon"></span>
		@if($proposal->token)
		<?php $url = 'proposals/'. $proposal->token.'/view';?>
		<a href="{{ $appUrl . $url }}" class="img-col" target="_blank">
		@else
		<a href="{{FlySystem::getUrl(config('jp.BASE_PATH').$proposal->file_path)}}" class="img-col" download target="blank">
		@endif
			<div class="img-thumb">
				<img src="{{getFileIcon($proposal->file_mime_type, $proposal->file_path)}}" alt="resource">
			</div>
			<span class="file-name">
				{{ $proposal->title ?? '' }}
			</span>
		</a>
	</div>
</div>