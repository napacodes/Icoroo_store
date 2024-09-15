@extends(view_path('master'))

@section('additional_head_tags')
<style>
	@if(config('app.top_cover'))
	#top-panel .cover {
		background-image: url('{{ asset_('storage/images/'.config('app.top_cover')) }}')
	}
	@else
	#top-panel .cover {
		background-image: url('{{ cover_mask() }}')
	}
	@endif
</style>

<script type="application/javascript">
	'use strict';
	window.props['products'] = @json($products, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
</script>
@endsection


@section('top-panel')
<div id="top-panel">
	<div class="cover">
		@if(auth_is_admin() && !config('app.top_cover'))
		<a class="refresh" @click="refreshTopPanelCover('homepage')">{{ __('Refresh') }}</a>
		@endif
	</div>
	<div class="header">{{ __(config('app.title')) }}</div>
	
	<form action="{{ route('home.products.q') }}" method="GET" class="ui form large">
		<div class="ui action input fluid">
		  <input type="text" name="q" required placeholder="{{ __('Search') }}...">
		  <button class="ui yellow button">{{ __('Search') }}</button>
		</div>
	</form>
</div>
@endsection

@section('body')

@if($newest_products->count())
<div class="selection latest">
	<a class="header">{{ __('Latest products') }}</a>
	
	<div class="ui five doubling cards products">
		@cards('axies-card', $newest_products, 'item')
	</div>
</div>
@endif


@if($trending_products->count())
<div class="selection popular">
	<a class="header">{{ __('Popular products') }}</a>
	
	<div class="ui five doubling cards products">
		@cards('axies-card', $trending_products, 'item')
	</div>
</div>
@endif


@if($featured_products->count())
<div class="selection featured">
	<a class="header">{{ __('Featured products') }}</a>
	
	<div class="ui five doubling cards products">
		@cards('axies-card', $featured_products, 'item')
	</div>
</div>
@endif

@if($free_products->count())
<div class="selection free">
	<a class="header">{{ __('Free products') }}</a>
	
	<div class="ui five doubling cards products">
		@cards('axies-card', $free_products, 'item')
	</div>
</div>
@endif

@if(config('app.blog.enabled'))
@if($posts->count())
<div class="selection featured">
	<a class="header" href="{{ route('home.blog') }}">{{ __('From our blog') }}</a>
	
	<div class="ui five doubling cards products">
		@foreach($posts as $post)
		<div class="fluid product card">
			<a class="image" href="{{ post_url($post->slug) }}">
				<img class="cover" src="/storage/posts/{{ $post->cover }}">
				<div class="category">{{ $post->category_name }}</div>
			</a>
			<a class="content title" href="{{ post_url($post->slug) }}">
				{{ $post->name }}
			</a>
		</div>
		@endforeach
	</div>
</div>
@endif
@endif

@endsection