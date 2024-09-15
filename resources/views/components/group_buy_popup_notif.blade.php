@if($item->group_buy_expiry && $item->group_buy_expiry > time())
<div id="group-buy-notif" :class="{added: cartHasGroupBuyItem(product)}" @click.once="groupBuyItem(product)">
	<div class="body">
		<div class="cover">
			<img loading="lazy" src="{{ asset_("storage/covers/{$item->cover}") }}">
		</div>
		<div class="content">
			<div class="text">{!! __('Get this item with :num more buyers for just', ['num' => '<span>'. $item->group_buy_min_buyers .'</span>']) !!}</div>
			<div class="price">{{ price($item->group_buy_price) }}</div>
			<div class="icon"><img src="/assets/images/fire.webp"></div>
		</div>
	</div>
	<div class="header">
		<div class="text">{{ __("Save money by purchasing this item with other buyers") }}</div>
		<div class="buyers">@{{ groupBuyBuyers }} {{ __("Buyer(s)") }}</div>
	</div>
</div>
@endif