@extends(view_path('master'))

@section('body')
	<div id="single-page">
		<div class="header">
			<div class="title">{{ $page->name }}</div>
		</div>

		<div class="body">
			{!! $page->content !!}
		</div>
	</div>
@endsection