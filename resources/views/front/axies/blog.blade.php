@extends(view_path('master'))

@section('additional_head_tags')
<style>
	@if(config('app.top_cover'))
	#top-panel .cover {
		background-image: url('{{ asset_('storage/images/'.config('app.top_cover')) }}')
	}
	@else
	#top-panel .cover {
		background-image: url('{{ cover_mask('blog') }}')
	}
	@endif
</style>
@endsection

@section('top-panel')
<div id="top-panel" class=blog>
	<div class="cover">
		@if(auth_is_admin() && !config('app.top_cover'))
		<a class="refresh" @click="refreshTopPanelCover('blog')">{{ __('Refresh') }}</a>
		@endif
	</div>

	<div class="header">{{ __(config('app.blog.title')) }}</div>
	
	<form action="" method="GET" class="ui form large">
		<div class="ui action input fluid">
		  <input type="text" name="q" placeholder="{{ __('Search') }}...">
		  <button class="ui yellow button">{{ __('Search') }}</button>
		</div>
	</form>
</div>
@endsection

@section('body')
	<div id="blog">
		
		<div class="ui secondary menu">
			<div class="item header">
				{{ __(':total Posts found.', ['total' => $posts->total()]) }}	
			</div>

			<div class="right menu">
				<div class="ui search item">
					<form class="ui icon input" action="{{ route('home.blog.q') }}" method="get">
						<input type="text" name="q" value="{{ request()->query('q') }}" placeholder="{{ __('Find a post') }}" class="prompt"> 
						<i class="search link icon"></i>
					</form>
				</div>

				<div class="item ui dropdown">
					<i class="bars icon mx-0"></i>
					<div class="menu">
						@foreach($posts_categories ?? [] as $posts_category)
						<a href="{{ blog_category_url($posts_category->slug) }}" class="item">{{ $posts_category->name }}</a>
						@endforeach
					</div>
				</div>
			</div>
		</div>

		@if($posts->count())
		<div class="ui four doubling cards">
			@foreach($posts as $post)
			<div class="ui fluid card">
				<a class="content cover" href="{{ route('home.post', $post->slug) }}">
					<img loading="lazy" src="{{ asset_("storage/posts/{$post->cover}") }}" alt="{{ __('cover') }}">
					<time>{{ $post->updated_at->format('M d, Y') }}</time>
				</a>
				<div class="content title">
					<a href="{{ route('home.post', $post->slug) }}">{{ $post->name }}</a>
				</div>
				<div class="content description">
					{{ shorten_str($post->short_description, 60) }}
				</div>
			</div>
			@endforeach
		</div>
		
		{{ $posts->appends(request()->q ? ['q' => request()->q] : [])->onEachSide(1)->links() }}
		{{ $posts->appends(request()->q ? ['q' => request()->q] : [])->links('vendor.pagination.simple-semantic-ui') }}
		@endif
	</div>

@endsection