@extends(view_path('master'))

@section('additional_head_tags')
@if(config('app.blog.disqus'))
<script defer>
    var disqus_config = function ()
    {
	    this.page.url = '{{ url()->current() }}';
	    this.page.identifier = '{{ $post->slug }}';
    };
    
    (function() {
    var d = document, s = d.createElement('script');
    s.src = 'https://tendra.disqus.com/embed.js';
    s.setAttribute('data-timestamp', +new Date());
    (d.head || d.body).appendChild(s);
    })();
</script>
<noscript>Please enable JavaScript to view the <a href="https://disqus.com/?ref_noscript">comments powered by Disqus.</a></noscript>
@endif
@endsection

@section('body')
	
	<div id="post">
		<div class="header">
			<div>{!! $post->name !!}</div>
			@if($post->short_description)
			<p>{{ $post->short_description }}</p>
			@endif
			<span>{{ __('Published at :date', ['date' => $post->updated_at]) }}</span>
		</div>

		<div class="body">
			<div class="column left">
				<div class="header">
					<div>{!! $post->name !!}</div>
					@if($post->short_description)
					<p>{{ $post->short_description }}</p>
					@endif
					<span>{{ __('Published at :date', ['date' => $post->updated_at]) }}</span>
				</div>
				
				<div class="post-cover">
					<img loading="lazy" src="{{ asset_("storage/posts/{$post->cover}") }}" alt="{{ $post->name }}">
				</div>
				
				<div class="post-content">
					<div class="post-body">
						{!! $post->content !!}
					</div>
				</div>

				<div class="social-buttons">
					<span>{{ __('Share on') }}</span>
					<div class="buttons">
						<button class="ui circular icon twitter button" onclick="window.open('https://twitter.com/intent/tweet?text={{ $post->short_description }}&url={{ url()->current() }}', 'Twitter', 'toolbar=0, status=0, width=\'auto\', height=\'auto\'')">
							<i class="twitter icon"></i>
						</button>

						<button class="ui circular icon vk button" onclick="window.open('https://vk.com/share.php?url={{ url()->current() }}', 'VK', 'toolbar=0, status=0, width=\'auto\', height=\'auto\'')">
							<i class="vk icon"></i>
						</button>

						<button class="ui circular icon tumblr button" onclick="window.open('https://www.tumblr.com/widgets/share/tool?canonicalUrl={{ url()->current() }}', 'tumblr', 'toolbar=0, status=0, width=\'auto\', height=\'auto\'')">
							<i class="tumblr icon"></i>
						</button>

						<button class="ui circular icon facebook button" onclick="window.open('https://facebook.com/sharer.php?u={{ url()->current() }}', 'Facebook', 'toolbar=0, status=0, width=\'auto\', height=\'auto\'')">
							<i class="facebook icon"></i>
						</button>

						<button class="ui circular icon pinterest button" onclick="window.open('https://www.pinterest.com/pin/create/button/?url={{ url()->current() }}&media={{ asset("storage/posts/$post->cover") }}&description={{ $post->short_description }}', 'Pinterest', 'toolbar=0, status=0, width=\'auto\', height=\'auto\'')">
							<i class="pinterest icon"></i>
						</button>

						<button class="ui circular icon linkedin button" onclick="window.open('https://www.linkedin.com/cws/share?url={{ url()->current() }}', 'Linkedin', 'toolbar=0, status=0, width=\'auto\', height=\'auto\'')">
							<i class="linkedin icon"></i>
						</button>
					</div>
				</div>
				
				@if($related_posts->count())
				<div class="related-posts">
					<div class="ui header">{{ __('Related posts') }}</div>
					<div class="ui three doubling stackable cards">
						@foreach($related_posts as $post)
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
				</div>
				@endif

				@if(config('app.blog.disqus'))
				<div id="blog-comments">
					<div id="disqus_thread"></div>
				</div>
				@endif
			</div>
		
			<div class="column right ">
				<div class="search">
					<form action="{{ route('home.blog.q') }}" method="get" id="posts-search" class="search-form">
						<div class="ui icon input fluid">
						  <input type="text" name="q" class="circular-corner" placeholder="{{ __('Find a post') }} ..." value="{{ request()->q }}">
						  <i class="search link icon"></i>
						</div>
					</form>
				</div>
				
				<div class="ui hidden divider"></div>

				<div class="categories">
					<div class="items-title">
						{{ __('Categories') }}
					</div>

					<div class="items-list">
						@foreach($posts_categories as $posts_category)
						<a href="{{ route('home.blog.category', $posts_category->slug) }}" class="item">
							<i class="caret right icon"></i>{{ $posts_category->name }}
						</a>
						@endforeach
					</div>
				</div>
				
				<div class="ui hidden divider"></div>

				<div class="latest-posts">
					<div class="items-title">
						{{ __('Latest posts') }}
					</div>

					<div class="items-list">
						@foreach($latest_posts as $latest_post)
						<a class="item" href="{{ route('home.post', $latest_post->slug) }}">
							<div class="cover" style="background-image: url({{ asset_("storage/posts/{$latest_post->cover}") }})"></div>
							<div class="content">
								<div class="name">{{ $latest_post->name }}</div>
								<div class="date">{{ $latest_post->updated_at->format('M d, Y') }}</div>
							</div>
						</a>
						@endforeach
					</div>
				</div>
			</div>
		</div>
	</div>

@endsection