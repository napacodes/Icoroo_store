<div class="ui card product fluid {{ $item->preview_type }} {{ out_of_stock($item, true) }} {{ has_promo_price($item, true) }}">
	<div class="image">
		<div class="ui inverted dimmer">
	    <div class="ui text loader">{{ __('Loading') }}</div>
	  </div>

	  @if(config('app.show_badges_on_the_product_card'))
			@if($item->trending)
			<div class="badge" title="{{ __('Trending') }}"><img src="/assets/images/trending.webp"></div>
			@elseif($item->featured)
			<div class="badge" title="{{ __('Featured') }}"><img src="/assets/images/featured.webp"></div>
			@endif
		@endif

		@if($item->preview_is('video'))
		<img class="cover" src="{{ asset_("storage/covers/".($item->cover ?? "default.webp")) }}">
		@else
		<a href="{{ item_url($item) }}" class="link">
			<img class="cover" src="{{ asset_("storage/covers/".($item->cover ?? "default.webp")) }}">
		</a>
		@endif

		@if(!is_null($item->price))
		<div class="price">{{ price($item->price) }}</div>
		@endif

		<a class="category" title="{{ $item->category_name }}" href="{{ category_url($item->category_slug) }}">{{ $item->category_name }}</a>

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
	</div>

	<a class="content title" href="{{ item_url($item) }}">
		{{ $item->name }}
	</a>
</div>