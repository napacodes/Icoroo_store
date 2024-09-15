@extends(view_path('master'))

@section('additional_head_tags')
<script>
	'use strict';

  window.props['product'] = {
		id: {{ $product->id }},
		name: '{{ $product->name }}',
		cover: '{{ asset("storage/covers/{$product->cover}") }}',
		quantity: 1,
		url: '{{ item_url($product) }}',
		license_id: '{{ $product->license_id }}',
		for_subscription: {{ var_export($product->for_subscriptions) }},
		@if(!$product->for_subscriptions)
		price: 0,
		@if($product->minimum_price)
		minimum_price: '{{ $product->minimum_price }}', 
	  custom_price: '',
	  @endif
		@endif
		slug: '{{ $product->slug }}',
		@if(productHasGroupBuy($product))
		groupBuy: {
			price: '{{ format_amount($product->group_buy_price) }}',
			min_buyers: {{ $product->group_buy_min_buyers }},
		},
		@endif
  }

  @if(!$product->for_subscriptions)
	  @if($product->product_prices[$product->license_id]['is_free'])
	  window.props['products']['price'] = 0;
	  @elseif($product->product_prices[$product->license_id]['has_promo'])
	  window.props['products']['price'] = '{{ price($product->product_prices[$product->license_id]['promo_price']) }}';
	  @else
	  window.props['products']['price'] = '{{ price($product->product_prices[$product->license_id]['price']) }}';
	  @endif

	  window.props['licenseId']  = '{{ $product->license_id }}';
	  window.props['itemPrices'] = @json($product->product_prices);
  @endif

  window.props['products'] = 	@json($similar_products->reduce(function ($carry, $item) 
															{
															  $carry[$item->id] = $item;
															  return $carry;
															}, []));
</script>
@endsection

@section('post_js')
@if($product->group_buy_price && (!$product->group_buy_expiry || $product->group_buy_expiry > time()))
<script>
	'use strict';

	function getGroupBuyBuyers()
	{
		fetch('{{ route('get_group_buy_buyers', ['product_id' => $product->id]) }}')
 		.then(res => res.json())
 		.then(data => 
 		{
 			if(data.hasOwnProperty('buyers'))
 			{
 				app.groupBuyBuyers = data.buyers;
 			}
 		})
	}

	getGroupBuyBuyers()

	@if($product->group_buy_price > 0 && (!$product->group_buy_expiry || $product->group_buy_expiry > time()))
 	setInterval(() => 
 	{
 		getGroupBuyBuyers()
 	}, 10000)
 	@endif
</script>
@endif
@endsection

@section('body')
	
{!! place_ad('ad_728x90') !!}

<div class="{{ $product->preview_type }}" id="item" vhidden>	
	<div id="header">
		<div class="container">
			<div class="thumb"><img src="{{ asset_("storage/covers/{$product->cover}") }}"></div>
			<div class="content">
				<div class="title">{{ $product->name }}</div>
				@if($product->short_description)
				<div class="description">{{ $product->short_description }}</div>
				@endif
			</div>
		</div>
	</div>

	@if(!$product->valid_subscription && $product->for_subscriptions && config('app.available_via_subscriptions_only_message'))
	<div class="ui fluid message">{!! __(config('app.available_via_subscriptions_only_message')) !!}</div>
	@endif

	@if(!$product->valid_subscription && !$product->for_subscriptions && !out_of_stock($product))
	<div class="purchase">
		<div class="ui menu fluid mx-0">
			<div class="item header {{ ($product->product_prices[$product->license_id]['has_promo'] ?? null) ? 'has-promo' : '' }}">
				@if($product->product_prices[$product->license_id]['is_free'])
				<span class="price default">{{ __('Free') }}</span>
				@elseif($product->product_prices[$product->license_id]['has_promo'])
				<span class="price promo">{{ price($product->product_prices[$product->license_id]['promo_price']) }}</span>
				<span class="price default">{{ price($product->product_prices[$product->license_id]['price']) }}</span>
				@else
				<span class="price default">{{ price($product->product_prices[$product->license_id]['price']) }}</span>
				@endif
			</div>

			@if($product->purchased && \Auth::check())
			<a class="item" 
					href="{{ route('home.download', ['type' => 'file', 'order_id' => $product->order_id, 'user_id' => Auth::id(), 'item_id' => $product->id]) }}"
				>{{ __('Download') }}</a>
			@endif

			@if($product->affiliate_link)
				<a href="{{ $product->affiliate_link }}" class="item">{{ __('Buy now') }}</a>
			@elseif(config('payments.enable_add_to_cart'))
				<div class="item cart-action ui dropdown nothing {{ count($product->product_prices) == 1 ? 'single' : '' }}">
					<div class="text">
						@if($product->product_prices[$product->license_id]['is_free'])
						{{ __('Download') }}
						@else
						{{ __('Buy now') }}
						@endif
					</div>
					<div class="menu">
						@if(!$product->product_prices[$product->license_id]['is_free'])
						<div class="item" data-value="buy-now">{{ __('Buy now') }}</div>
						@endif
						<div class="item" data-value="add-to-cart">{{ __('Add To Cart') }}</div>
					</div>
				</div>
			@else
				@if(!$product->product_prices[$product->license_id]['is_free'])
				<a class="item cart-action" @click="buyNow(product)">{{ __('Buy now') }}</a>
				@else
				<a class="item cart-action" 
					href="{{ route('home.download', ['type' => 'file', 'order_id' => rand(100,9999), 'user_id' => Auth::id() ?? rand(100,9999), 'item_id' => $product->id]) }}"
				>{{ __('Download') }}</a>
				@endif
			@endif

			@if(!$product->affiliate_link)
				<div class="item ui dropdown licenses {{ count($product->product_prices) <= 1 ? 'd-none disabled' : '' }}" :class="{'d-none': !itemHasPaidLicense()}">
					<div class="text">{{ __(mb_ucfirst($product->license_name)) }}</div>
					<input type="hidden" name="license_id" @change="setPrice($event)">
					<div class="menu">
						@foreach($product->product_prices as $product_price)
						<div class="item" data-value="{{ $product_price['license_id'] }}">{{ $product_price['license_name'] }}</div>
						@endforeach
					</div>
				</div>

				@if(!is_null($product->minimum_price) && !$product->for_subscriptions)
				<div class="item custom-price" title="{{ __("Minimum price :price", ['price' => price($product->minimum_price, false)]) }}">
					<div class="ui transparent input">
						<input class="circular-corner" type="number" step="0.000000001" @change="setCustomItemPrice" placeholder="{{ __('Custom price') }}">
					</div>
				</div>
				@endif
			@endif
		</div>
	</div>
	@endif

	@if($product->valid_subscription && !out_of_stock($product) && \Auth::check())
	<div class="purchase">
		<div class="ui menu fluid mx-0">
			<a class="item header" 
					href="{{ route('home.download', ['type' => 'file', 'order_id' => $product->transaction_id, 'user_id' => Auth::id(), 'item_id' => $product->id]) }}"
				>{{ __('Download') }}</a>
		</div>
	</div>
	@endif

	@if(out_of_stock($product))
	<div class="ui fluid message">{{ __('This item is out of stock') }}</div>
	@endif

	<div class="time-counter">
		<span class="text">{{ __('Ends in') }}</span>
		<span class="time"></span>
	</div>
	
	<div class="row main">
		<div class="column mx-auto l-side">
			<div class="ui top unstackable secondary menu p-1">
				@if(config('app.show_streaming_player') && (auth_is_admin() || $product->purchased || $product->valid_subscription) && itemHasVideo($product))
				<a class="item" data-tab="streaming">{{ __('Streaming') }}</a>
				@endif

			  <a class="active item ml-0" data-tab="details">{{ __('Description') }}</a>
			  
			  @if($product->hidden_content && (auth_is_admin() || $product->purchased || $product->valid_subscription))
			  <a class="item" data-tab="hidden-content">{{ __('Hidden content') }}</a>
				@endif
				
				@if($product->table_of_contents)
				<a class="item" data-tab="table_of_contents">{{ __('Table of contents') }}</a>
				@endif

			  @if(config('app.enable_comments'))
				<a class="item" data-tab="support">{{ __('Comments') }}</a>
				@endif

				@if(config('app.enable_reviews'))
				<a class="item" data-tab="reviews">{{ __('Reviews') }}</a>
				@endif

			  <a class="item mr-0" data-tab="faq">{{ __('FAQ') }}</a>
			</div>

			<div class="row item">
				{{-- Streaming --}}
				@if(config('app.show_streaming_player') && (auth_is_admin() || $product->purchased || $product->valid_subscription) && itemHasVideo($product))
				<div class="sixteen wide column streaming">
					<div class="stream-player player" data-type="video">
						<div class="video"></div>
						<a class="download" @click="downloadItem({{ $product->id }}, '#download')"><img src="/assets/images/download.webp"></a>
						<video 
							@if(preg_match("/^local|yandex$/i", $product->file_host))
							src="{{ route('stream_vid', ['id' => $product->id, 'temp_url' => base64_encode($product->temp_direct_url)]) }}"
							@else 
							src="{{ $product->temp_direct_url }}"
							@endif
							type="{{ itemHasVideo($product) }}"></video>
						<div class="controls">
							<div class="play" title="{{ __('Play/Pause') }}"><img src="/assets/images/play-3.png"></div>
							<div class="wave"><span class="time"></span></div>
							<div class="current-time" title="{{ __('Current time') }}">00:00:00</div>
							<div class="volume" title="{{ __('Volume') }}">
								<img src="/assets/images/volume.png">
								<div><span><span></span></span></div>
							</div>
							<div class="stop" title="{{ __('Stop') }}"><img src="/assets/images/stop-2.png"></div>
							<div class="maximize" title="{{ __('Fullscreen') }}"><img src="/assets/images/maximize.png"></div>
						</div>
					</div>
				</div>
				@endif

				{{-- Details --}}
				<div class="sixteen wide column details">
					@if($product->screenshots)
					<div class="ui fluid card screenshots">
						<div class="content images body">
							<div class="ui items">
								@foreach($product->screenshots as $screenshot)
								<a class="item screenshot" data-src="{{ $screenshot }}" style="background-image: url('{{ $screenshot }}')"></a>
								@endforeach
							</div>
						</div>
					</div>
					@endif
					
					@if($product->overview)
					<div class="ui fluid card">
						<div class="content overview body">
							{!! $product->overview !!}
						</div>
					</div>
					@endif

					<div class="sharer">					
						<div class="buttons">
							<a target="_blank" href="{{ share_link('twitter', $product->permalink ?? null) }}" class="item" title="{{ __('Twitter') }}"><img src="/assets/images/social/twitter.webp"></a>
							<a target="_blank" href="{{ share_link('tumblr', $product->permalink ?? null) }}" class="item" title="{{ __('Tumblr') }}"><img src="/assets/images/social/tumblr.webp"></a>
							<a target="_blank" href="{{ share_link('vk', $product->permalink ?? null) }}" class="item" title="{{ __('vKontakte') }}"><img src="/assets/images/social/vkontakte.webp"></a>
							<a target="_blank" href="{{ share_link('pinterest', $product->permalink ?? null) }}" class="item" title="{{ __('Pinterest') }}"><img src="/assets/images/social/pinterest.webp"></a>
							<a target="_blank" href="{{ share_link('facebook', $product->permalink ?? null) }}" class="item" title="{{ __('Facebook') }}"><img src="/assets/images/social/facebook.webp"></a>
							<a target="_blank" href="{{ share_link('linkedin', $product->permalink ?? null) }}" class="item" title="{{ __('Linkedin') }}"><img src="/assets/images/social/linkedin.webp"></a>
							<a target="_blank" href="{{ share_link('reddit', $product->permalink ?? null, $product->name) }}" class="item" title="{{ __('Reddit') }}"><img src="/assets/images/social/reddit.webp"></a>
							<a target="_blank" href="{{ share_link('okru', $product->permalink ?? null) }}" class="item" title="{{ __('Ok.ru') }}"><img src="/assets/images/social/okru.webp"></a>
							<a target="_blank" href="{{ share_link('skype', $product->permalink ?? null, $product->name) }}" class="item" title="{{ __('Skype') }}"><img src="/assets/images/social/skype.webp"></a>
							<a target="_blank" href="{{ share_link('telegram', $product->permalink ?? null, $product->name) }}" class="item" title="{{ __('Telegram') }}"><img src="/assets/images/social/telegram.webp"></a>
							<a target="_blank" href="{{ share_link('whatsapp', $product->permalink ?? null, $product->name) }}" class="item" title="{{ __('Whatsapp') }}"><img src="/assets/images/social/whatsapp.webp"></a>
							<a target="_blank" class="item" v-if="canShare && deviceIsMobile" id="share-api"><img src="/assets/images/social/share.webp"></a>
						</div>
					</div>	
				</div>
				
				{{-- Hidden Content --}}
				@if($product->hidden_content && (auth_is_admin() || $product->purchased || $product->valid_subscription))
				<div class="sixteen wide column hidden-content">
					{!! $product->hidden_content !!}
				</div>
				@endif

				{{-- Table of contents --}}
				@if($product->table_of_contents)
				<div class="sixteen wide column table_of_contents">
					<div class="ui segments shadowless">
						@foreach($product->table_of_contents as $title)
							@if($title->text_type === 'header')
								<div class="ui secondary segment">
							    <p>{{ $title->text }}</p>
							  </div>
							@else
								<div class="ui segment">
									<p>
										@if($title->text_type === 'subheader')
										<i class="right blue angle icon"></i>
										@else
										<span class="ml-2"></span>
										@endif

										{{ $title->text }}
									</p>
							  </div>
							@endif
					  @endforeach
					</div>
				</div>
				@endif

				{{-- Support --}}
				<div class="sixteen wide column support">

					@if(session('comment_response'))
					<div class="ui fluid shadowless borderless green basic message circular-corner">
						{{ request()->session()->pull('comment_response') }}
					</div>
					@endif

					@if(!$comments->count())
					<div class="ui fluid shadowless borderless message rounded-corner">
						{{ __('No comments found') }}.
					</div>
					@endif

					<div class="ui unstackable items mt-1">
						<div class="mb-1">
							@foreach($comments as $comment)
							<div class="comments-wrapper">
								<div class="item main-item parent">
									<div class="main">
										<div class="ui tiny circular image">
											<img src="{{ asset_("storage/avatars/".$comment->avatar) }}">
										</div>

										<div class="content description body">
											<h3>
												{{ $comment->name ?? $comment->alias_name ?? $comment->fullname }} 
												<span class="floated right">{{ $comment->created_at->diffForHumans() }}</span>
											</h3>

											{!! nl2br($comment->body) !!}
											
											<div class="ui form">
												@auth
												<div class="ui icon bottom right white pointing dropdown button like">
													<img src="{{ asset_('assets/images/like.png') }}" class="ui image m-0">
												  <div class="menu">
												    <div class="item reactions" data-item_id="{{ $comment->id }}" data-item_type="comment">
												    	<a class="action" data-reaction="like" style="background-image: url('{{ asset_('assets/images/reactions/like.gif') }}')"></a>
												    	<a class="action" data-reaction="love" style="background-image: url('{{ asset_('assets/images/reactions/love.gif') }}')"></a>
												    	<a class="action" data-reaction="funny" style="background-image: url('{{ asset_('assets/images/reactions/funny.gif') }}')"></a>
												    	<a class="action" data-reaction="wow" style="background-image: url('{{ asset_('assets/images/reactions/wow.gif') }}')"></a>
												    	<a class="action" data-reaction="sad" style="background-image: url('{{ asset_('assets/images/reactions/sad.gif') }}')"></a>
												    	<a class="action" data-reaction="angry" style="background-image: url('{{ asset_('assets/images/reactions/angry.gif') }}')"></a>
												    </div>
												  </div>
												</div>

												@endauth

												@if(config('app.enable_subcomments'))
												<button class="ui blue basic button mr-0 uppercase rounded-corner"
																@click="setReplyTo('{{ $comment->name ?? $comment->alias_name ?? $comment->fullname }}', {{ $comment->id }})">
													{{ __('Reply') }}
												</button>
												@endif

												@if(config('app.can_delete_own_comments'))
												<a class="ui basic button mr-0 ml-1 uppercase rounded-corner delete" @click="deleteComment('{{ route('delete_comment', ['id' => $comment->id, 'redirect' => url()->current().'?tab=comments']) }}')">
													{{ __('Delete') }}
												</a>
												@endif
											</div>
										</div>
									</div>

									<div class="extra">
										@if(count($comment->reactions ?? []))
										<div class="saved-reactions" data-item_id="{{ $comment->id }}" data-item_type="comment">
											@foreach($comment->reactions as $name => $count)
											<span class="reaction" data-reaction="{{ $name }}" data-tooltip="{{ $count }}" data-inverted="" style="background-image: url('{{ asset_("assets/images/reactions/{$name}.png") }}')"></span>
											@endforeach
										</div>
										@endif

										<div class="count">
											<span>{{ __(':count Comments', ['count' => $comment->children->count()]) }}</span>
										</div>
									</div>
								</div>

								@if(config('app.enable_subcomments'))
								@foreach($comment->children as $child)
								<div class="item main-item children">
									<div class="main">
										<div class="ui tiny circular image">
											<img src="{{ asset_("storage/avatars/".$child->avatar) }}">
										</div>

										<div class="content description body">
											<h3>
												{{ $child->name ?? $child->alias_name ?? $child->fullname }} 
												<span class="floated right">{{ $child->created_at->diffForHumans() }}</span>
											</h3>

											{!! nl2br($child->body) !!}
											
											<div class="ui form">
												@auth
												<div class="ui icon bottom right white pointing dropdown button like">
													<img src="{{ asset_('assets/images/like.png') }}" class="ui image m-0">
												  <div class="menu">
												    <div class="item reactions" data-item_id="{{ $child->id }}" data-item_type="comment">
												    	<a class="action" data-reaction="like" style="background-image: url('{{ asset_('assets/images/reactions/like.gif') }}')"></a>
												    	<a class="action" data-reaction="love" style="background-image: url('{{ asset_('assets/images/reactions/love.gif') }}')"></a>
												    	<a class="action" data-reaction="funny" style="background-image: url('{{ asset_('assets/images/reactions/funny.gif') }}')"></a>
												    	<a class="action" data-reaction="wow" style="background-image: url('{{ asset_('assets/images/reactions/wow.gif') }}')"></a>
												    	<a class="action" data-reaction="sad" style="background-image: url('{{ asset_('assets/images/reactions/sad.gif') }}')"></a>
												    	<a class="action" data-reaction="angry" style="background-image: url('{{ asset_('assets/images/reactions/angry.gif') }}')"></a>
												    </div>
												  </div>
												</div>

												@endauth

												@if(config('app.enable_subcomments'))
												<button class="ui blue basic rounded-corner button mr-0 uppercase"
																@click="setReplyTo('{{ $child->name ?? $child->alias_name ?? $child->fullname }}', {{ $comment->id }})">
													{{ __('Reply') }}
												</button>
												@endif

												@if(config('app.can_delete_own_comments'))
												<a class="ui button mr-0 uppercase rounded-corner ml-1 basic delete" @click="deleteComment('{{ route('delete_comment', ['id' => $child->id, 'redirect' => url()->current().'?tab=comments']) }}')">
													{{ __('Delete') }}
												</a>
												@endif
											</div>
										</div>
									</div>

									@if(count($child->reactions ?? []))
									<div class="extra">
										<div class="saved-reactions" data-item_id="{{ $child->id }}" data-item_type="comment">
											@foreach($child->reactions as $name => $count)
											<span class="reaction" data-reaction="{{ $name }}" data-tooltip="{{ $count }}" data-inverted="" style="background-image: url('{{ asset_("assets/images/reactions/{$name}.png") }}')"></span>
											@endforeach
										</div>
									</div>
									@endif
								</div>
								@endforeach
								@endif
							</div>
							@endforeach
						</div>
						
						@auth

						<form class="item ui form" method="post" action="{{ item_url($product) }}">
							@csrf
							<input type="hidden" name="redirect_url" value="{{ url()->current() }}?tab=comments">
							<input type="hidden" name="type" value="comments" class="d-none">
							<input type="hidden" name="edit_comment_id" :value="commentId" class="d-none">
							
							@if(config('app.enable_subcomments'))
							<input type="hidden" name="comment_id" :value="replyTo.commentId" class="d-none">
							@endif

							<div class="ui tiny rounded image">
					    	<img src="{{ asset_("storage/avatars/" . (auth()->user()->avatar ?? 'default.webp')) }}">
					    </div>
					    	
					    <div class="content pl-1">
					    	<label>
					    		{{ __('Post a comment') }}

					    		@if(config('app.enable_subcomments'))
									<div class="ui blue basic label mb-1-hf capitalize reply-to" v-if="replyTo.userName !== null && commentId == null">
										@{{ replyTo.userName }}
										<i class="delete link icon mr-0" @click="resetReplyTo"></i>
									</div>
									@endif

									<div class="edit-comment" v-if="replyTo.userName == null && commentId !== null">
										@{{ __('Edit comment') }}
										<i class="delete link icon mr-0" @click="resetEditCommentId"></i>
									</div>
					    	</label>	

								<textarea rows="5" name="comment" placeholder="{{ __('Your comment') }} ..."></textarea>
								<button type="submit" class="ui yellow circular button right floated mt-1-hf">{{ __('Submit') }}</button>
							</div>

						</form>

						@else

						<div class="ui fluid blue shadowless borderless message circular-corner">
							{!! __(':sign_in to post a comment', ['sign_in' => '<a href="'.route('login', ['redirect' => url()->current()]).'">'.__("Login").'</a>']) !!}
						</div>

						@endauth
					</div>
				</div>

				{{-- Reviews --}}
				<div class="sixteen wide column reviews">
					@if(session('review_response'))
					<div class="ui fluid shadowless borderless green basic message circular-corner">
						{{ request()->session()->pull('review_response', 'default') }}
					</div>
					@elseif(!$reviews->count())
					<div class="ui fluid shadowless borderless message rounded-corner">
						{{ __('This item has not received any review yet') }}.
					</div>
					@endif

					@if($reviews->count())
					<div class="ui unstackable items">
						@foreach($reviews as $review)
						<div class="item">
							<div class="ui tiny circular image">
								<img src="{{ asset_("storage/avatars/".$review->avatar) }}">
							</div>

							<div class="content description body">
								<h3>
									{{ $review->name ?? $review->alias_name ?? $review->fullname }} 
									<span class="floated right">{{ $review->created_at->diffForHumans() }}</span>
								</h3>

								<h4 class="mt-1-hf">
									<span class="image rating">{!!  item_rating($review->rating) !!}</span>
								</h4>

								{{ nl2br($review->content) }}
							</div>
						</div>
						@endforeach
					</div>
					@endif

					@auth
					{{-- IF PURCHASED AND NOT REVIEWED YET --}}
					@if(!$product->reviewed && $product->purchased)
					
					<div class="ui items borderless">
						<form class="item ui form" method="post" action="{{ item_url($product) }}">
							@csrf
	
							<div class="ui tiny circular image">
								<img src="{{ asset_("storage/avatars/" . (auth()->user()->avatar ?? 'default.webp')) }}">
								<input type="hidden" name="type" value="reviews" class="none">
							</div>
								
							<div class="content pl-1">
								<span class="ui star rating active mb-1-hf" data-max-rating="5"></span>
								<input type="hidden" name="rating" class="d-none">
											
								<textarea rows="5" name="review" placeholder="Your review ..."></textarea>
	
								<button type="submit" class="ui yellow circular button right floated mt-1-hf uppercase">{{ __('Submit') }}</button>
							</div>
						</form>
					</div>
					
					@endif
					@else
				
					<div class="ui fluid blue shadowless borderless message circular-corner">
						{!! __(':sign_in to review this item', ['sign_in' => '<a href="'.route('login', ['redirect' => url()->current()]).'">'.__("Login").'</a>']) !!}
					</div>

					@endauth
				</div>
				
				{{-- FAQ --}}
				<div class="sixteen wide column faq">
					@if($product->faq)
					<div class="ui divided list">
						@foreach($product->faq as $qa)
						<div class="item p-1">
							<div class="header mb-1">{{ __('Q') }}. {{ $qa->question }}</div>
							<strong>{{ __('A') }}.</strong> {{ $qa->answer }}
						</div>
						@endforeach
					</div>
					@else
					<div class="ui fluid shadowless borderless message rounded-corner">
						{{ __('No Questions / Answers added yet.') }}
					</div>
					@endif
				</div>

			</div>
		</div>
	
		<div class="column mx-auto r-side">
			<div class="ui fluid card item-details">
				<div class="content title">
					<div class="ui header">{{ __('Item details') }}</div>
				</div>
				<div class="content borderless">
					<table class="ui unstackable large table basic">
						@if($product->preview_url)
						<tr>
							<td><strong>{{ __('Preview') }}</strong></td>
							<td><a href="{{ $product->preview_url }}"><i class="eye icon mx-0"></i></a></td>
						</tr>
						@endif

						@if($product->version)
						<tr>
							<td><strong>{{ __('Version') }}</strong></td>
							<td>{{ $product->version }}</td>
						</tr>
						@endif

						@if($product->category)
						<tr>
							<td><strong>{{ __('Category') }}</strong></td>
							<td>{{ $product->category->name }}</td>
						</tr>
						@endif

						@if($product->release_date)
						<tr>
							<td><strong>{{ __('Release date') }}</strong></td>
							<td>{{ $product->release_date }}</td>
						</tr>
						@endif
						
						@if($product->last_update)
						<tr>
							<td><strong>{{ __('Latest update') }}</strong></td>
							<td>{{ $product->last_update }}</td>
						</tr>
						@endif

						@if($product->included_files)
						<tr>
							<td><strong>{{ __('Included files') }}</strong></td>
							<td>{{ $product->included_files }}</td>
						</tr>
						@endif

						@if($product->compatible_browsers)
						<tr>
							<td><strong>{{ __('Compatible browsers') }}</strong></td>
							<td>{{ $product->compatible_browsers }}</td>
						</tr>
						@endif

						@foreach($product->additional_fields ?? [] as $field)
						<tr>
							<td><strong>{{ __($field->_name_) }}</strong></td>
							<td>{!! $field->_value_ !!}</td>
						</tr>
						@endforeach

						<tr>
							<td><strong>{{ __('Comments') }}</strong></td>
							<td>{{ $product->comments_count }}</td>
						</tr>

						@if(config('app.show_rating.product_page'))
						<tr>
							<td><strong>{{ __('Rating') }}</strong></td>
							<td><div class="image rating justify-content-flex-end">{!! item_rating($product->rating ?? '0') !!}</div></td>
						</tr>
						@endif

						@if($product->high_resolution)
						<tr>
							<td><strong>{{ __('High resolution') }}</strong></td>
							<td>{{ $product->high_resolution ? 'Yes' : 'No' }}</td>
						</tr>
						@endif

						@if(config('app.show_sales.product_page'))
						<tr>
							<td><strong>{{ __('Sales') }}</strong></td>
							<td>{{ $product->sales }}</td>
						</tr>
						@endif
					</table>
				</div>
			</div>

			@if($product->tags)
			<div class="ui fluid card tags">
				<div class="content">
					<div class="ui header">{{ __('Item tags') }}</div>
				</div>
				<div class="content borderless">
					<div class="ui labels">
						@foreach($product->tags as $tag)
						<a href="{{ route('home.products.q', ['tags' => $tag]) }}" class="ui circular large basic label">{{ $tag }}</a>
						@endforeach
					</div>
				</div>
			</div>
			@endif
		</div>
	</div>
	
	<div class="ui modal" id="screenshots" >
	  <div class="image content p-0" v-if="activeScreenshot">
			<div class="left">
				<button class="ui icon button" type="button" @click="slideScreenhots('prev')">
				  <i class="angle big left icon m-0"></i>
				</button>
			</div>

	    <img class="image" :src="activeScreenshot">

	    <div class="right">
		    <button class="ui icon button" type="button" @click="slideScreenhots('next')">
				  <i class="angle big right icon m-0"></i>
				</button>
	    </div>
	  </div>
	</div>


	<div class="ui modal" id="reactions">
		<div class="header">
			<div class="wrapper">
				<a v-for="reaction, name in usersReactions" :class="['name ' + name, usersReaction === name ? 'active' : '']" :data-reaction="name">
					<span class="label">@{{ name }}</span>
					<span class="count">@{{ reaction.length }}</span>
				</a>
			</div>
		</div>
		<div class="content">
			<div class="wrapper">
				<div v-for="reaction, name in usersReactions" :class="['users ' + name, usersReaction === name ? 'active' : '']">
					<div class="user" v-for="user in reaction" :title="user.user_name">
						<span class="avatar"><img :src="'/storage/avatars/' + user.user_avatar" class="ui avatar image"></span>
						<span class="text">@{{ user.user_name }}</span>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

@if($similar_products->count())
<div class="row w-100" id="similar-items">
	<div class="header">
		<div>{{ __('Similar items') }}</div>
	</div>

	<div class="ui five doubling cards @if(config('app.masonry_layout')) is_masonry @endif">
		@cards('tendra-card', $similar_products, 'item', ['category' => 1, 'rating' => 1])
	</div>
</div>
@endif

@if($product->group_buy_price)
@include('components.group_buy_popup_notif', ['item' => $product])
@endif

@endsection