@extends(view_path('master'))

@section('additional_head_tags')
<style>
	@if(config('app.top_cover'))
	#top-search {
		background-image: url('{{ asset_('storage/images/'.config('app.top_cover')) }}')
	}

	#top-search:before {
		opacity: 0;
	}
	@endif
</style>

<script type="application/javascript">
	'use strict';
	window.props['products'] = @json($products, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
</script>
@endsection

@section('top-search')
	<div class="ui bottom attached basic segment" id="top-search">
		<div class="ui middle aligned grid m-0">
			<div class="row">
				<div class="column center aligned">
					
					@if(config('app.search_header'))
					<div class="header">{{ __(config('app.search_header')) }}</div>
					@endif
					
					<form class="ui huge form fluid search-form" id="live-search" method="get" action="{{ route('home.products.q') }}">
						<div class="ui action input fluid">
						  <input type="text" name="q" placeholder="{{ __('Search') }}...">
						  <button class="ui large button mx-0">{{ __('Search') }}</button>
						</div>
						<div class="products" vhidden>
							<a :href="'/item/'+ item.id + '/' + item.slug" v-for="item of liveSearchItems" class="item">
								@{{ item.name }}
							</a>
						</div>
			    </form>

				</div>
			</div>
		</div>
	</div>
@endsection


@section('body')
	
	<div class="row home-items">
		
		{{--  NEWEST PRODUCTS --}}
		@if($newest_products->count())
		<div class="newest wrapper">
			<div class="ui header">
				{{ __('Our Newest Items') }}
				<div class="sub header">
					{{ __('Explore our newest Digital Products, from :first_category to :last_category, we always have something interesting for you.',
					['first_category' => collect(config('categories.category_parents'))->first()->name ?? null, 
					 'last_category' => collect(config('categories.category_parents'))->last()->name ?? null]) }}
				</div>
			</div>

			<div class="ui eight items {{ is_single_prdcts_type() }}">
				@foreach($newest_products as $newest_product)
				<a href="{{ item_url($newest_product) }}" class="item" title="{{ $newest_product->name }}">
					<div class="cover" style="background-image: url({{ asset_("storage/covers/{$newest_product->cover}") }})"></div>
					<div class="title">{{ \Str::limit($newest_product->name, 50) }}</div>
				</a>
				@endforeach
			</div>
		</div>

		<div class="ui popup newest-item">
			<div class="ui fluid card">
				<div class="image">
					<img src="">
					<div class="price"></div>
					<div class="play">
						<a><img src="{{ asset_('assets/images/play.png') }}"></a>
					</div>
				</div>
				<div class="content">
					<div class="name"></div>
				</div>
			</div>
		</div>
		@endif


		{{--  FEATURED PRODUCTS --}}
		@if($featured_products)
		<div class="featured wrapper" id="featured-items">
			<div class="ui header">
				{{ __('Featured Items Of The Week') }}
				<div class="sub header">
					{{ __('Explore our best items of the week. :categories and more.',
					['categories' => implode(', ', array_map(function($category)
					{
						return __($category->name ?? null);
					}, config('categories.category_parents') ?? []))]) }}
				</div>
			</div>
			
			<div class="ui secondary menu">
    			@foreach($featured_products as $category_slug => $items_list)
        			@foreach(config('categories.category_parents') ?? [] as $category)
            			@if($category_slug == $category->slug)
        				<a class="item tab {{ $loop->parent->first ? 'active' : '' }}" data-category="{{ $category->slug }}">{{ $category->name }}</a>
        				@endif
    				@endforeach
    			@endforeach
			</div>

			@foreach($featured_products as $category_slug => $items_list)
			<div class="ui four doubling cards mt-0 {{ $category_slug }} {{ $loop->first ? 'active' : '' }}">
				@cards('tendra-card', $items_list, 'item', ['category' => 1, 'sales' => 0, 'rating' => 1, 'home' => 1])
			</div>
			@endforeach
		</div>
		@endif
	

		{{-- SUBSCRIPTION PLANS --}}
		@if(config('app.subscriptions.enabled') && $subscriptions->count())
		<div class="pricing container">
			<div class="pricing wrapper">
				<div class="ui header">
					{{ __('Our Pricing Plans') }}
					<div class="sub header">
						{{ __('Explore our pricing plans, from :first to :last, choose the one that meets your needs.', ['first' => $subscriptions->first()->name, 'last' => $subscriptions->last()->name]) }}
					</div>
				</div>
				
				<div class="ui three doubling cards mt-2">
					@foreach($subscriptions as $subscription)
					<div class="card">
						<div class="contents">
							<div class="content price">
								<div style="color: {{ $subscription->color ?? '#000' }}">
									{{ price($subscription->price) }}
									@if($subscription->title)<span>/ {{ __($subscription->title) }}</span>@endif
								</div>
							</div>

							<div class="content description">
								@if(strip_tags($subscription->description))
								{!! $subscription->description !!}
								@elseif($subscription->specifications)
									@foreach($subscription->specifications ?? [] as $specification)
									<div class="item">
										<span class="icon {{ $specification->included ? 'included' : '' }}"><img src="/assets/images/checkbox-2.webp"></span>
										<div class="text">{{ $specification->text }}</div>
									</div>
									@endforeach
								@endif
							</div>

							<div class="content buy">
								<a href="{{ pricing_plan_url($subscription) }}" class="ui large circular button mx-0" style="background: {{ $subscription->color ?? '#667694' }}">
									{{ __('Get started') }}
								</a>
							</div>

							<div class="name" style="background: {{ $subscription->color ?? '#667694' }}">
								<span>{{ __($subscription->name) }}</span>
							</div>
						</div>
					</div>
					@endforeach
				</div>
			</div>
		</div>
		@endif


		{{-- POSTS --}}
		@if(config('app.blog.enabled'))
		@if($posts->count())
			<div class="posts wrapper">
				<div class="ui header">
					{{ __('Our Latest News') }}
					<div class="sub header">
						{{ __('Explore our latest articles for more ideas and inspiration, technology, design, tutorials, business and much more.') }}
					</div>
				</div>

				<div class="ui three doubling cards mt-2">
					@foreach($posts as $post)
					<div class="card">
						<a class="image" href="{{ route('home.post', $post->slug) }}">
							<img src="{{ asset_("storage/posts/{$post->cover}") }}" alt="{{ $post->name }}">
						</a>

						<div class="content metadata">
							<div class="left">
								<div>{{ $post->updated_at->format('d') }}</div>
								<div>{{ $post->updated_at->format('M, Y') }}</div>
							</div>
							<div class="right">
								<a href="{{ route('home.post', $post->slug) }}" title="{{ $post->name }}">{{ shorten_str($post->name, 60) }}</a>
							</div>
						</div>

						<div class="content description">
							{{ shorten_str($post->short_description, 100) }}
						</div>

						<div class="content action">
							<a href="{{ route('home.post', $post->slug) }}">{{ __('Read more') }}<i class="plus icon ml-1-hf"></i></a>
						</div>
					</div>
					@endforeach
				</div>
			</div>
		@endif
		@endif
		
	</div>

@endsection