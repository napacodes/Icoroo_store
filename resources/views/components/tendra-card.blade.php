<div class="ui card product fluid {{ $item->preview_type }} {{ out_of_stock($item, true) }} {{ has_promo_price($item, true) }}">
	<div class="content header">
		<span class="cover-mask"></span>

		@if(config('app.show_badges_on_the_product_card'))
			@if($item->trending)
			<div class="badge" title="{{ __('Trending') }}"><img src="/assets/images/trending.webp"></div>
			@elseif($item->featured)
			<div class="badge" title="{{ __('Featured') }}"><img src="/assets/images/featured.webp"></div>
			@endif
		@endif

		<img loading="lazy" src="{{ asset_("storage/covers/".($item->cover ?? "default.webp")) }}" alt="cover">

		<div class="price {{ $item->promotional_price ? 'has-promo' : '' }} {{ !$item->price ? 'free' : '' }}">
			{{ price($item->promotional_price ?? $item->price) }}
		</div>

		@if(!$item->has_preview('video') && !$item->has_preview('audio'))
			<a class="link" href="{{ item_url($item) }}" title="{{ $item->name }}"></a>

			@if(!$item->for_subscriptions && config('app.show_add_to_cart_button_on_the_product_card'))
				@if(config('payments.enable_add_to_cart'))
				<div class="item-action add-to-cart" data-item="{!! base64_encode(json_encode(['id' => $item->id, 'license_id' => $item->license_id])) !!}">
					<span>{{ __('Add to cart') }}</span>
				</div>
				@else
				<div class="item-action buy-now" data-item="{!! base64_encode(json_encode(['id' => $item->id, 'license_id' => $item->license_id])) !!}">
					<span>{{ __('Buy now') }}</span>
				</div>
				@endif
			@endif
		@endif

		@if($item->preview_is('audio'))
		<div class="player" data-type="audio" data-ready="false">
			<audio controls="" src="{{ isUrl($item->preview) ? $item->preview : asset_("storage/previews/{$item->preview}") }}" class="d-none"></audio>
			<div class="controls">
				<div class="play"><img src="/assets/images/pause.png"></div>
				<div class="wave"><span class="time"></span></div>
				<div class="stop"><img src="/assets/images/stop.png"></div>
			</div>
		</div>
		@elseif($item->preview_is('video'))
		<div data-src="{{ preview_link($item) }}" class="video">
			<img src="/assets/images/play-2.png">		
		</div>
		@endif
	</div>

	<div class="content body {{ $item->for_subscriptions ? 'padded' : '' }}">
			<a href="{{ item_url($item) }}" title="{{ $item->name }}">
		    {{ \Str::limit($item->name, 50) }}
			</a>
	</div>

	<div class="content footer">
		@if($category ?? null)
		<a class="category" href="{{ route('home.products.category', ['category_slug' => $item->category_slug]) }}">
    {{ $item->category_name }}
  	</a>
  	@endif

  	@if(($rating ?? null) && config('app.show_rating.product_card'))
  	<div class="image rating">{!! item_rating(4) !!}</div>
		@endif
	</div>
</div>