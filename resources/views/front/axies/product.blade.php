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

  $(() =>
  {
  	try
  	{
  		$('.stream-player video')[0].currentTime = 1;
  	}
  	catch(e){}
  })
</script>
@endsection


@section('body')
<div class="{{ $product->file_type ?? $product->preview_type }}" id="item">	
	<div class="ui big breadcrumb">
	  <a class="section" href="/">{{ __('Home') }}</a>
	  <i class="right chevron icon divider"></i>
	  <a class="section" href="{{ category_url($product->category->slug) }}">{{ $product->category->name }}</a>
	  <i class="right chevron icon divider"></i>
	  <div class="active section">{{ $product->name }}</div>
	</div>

	<div class="container">
		<div class="left-side">
			<div class="ui big breadcrumb">
			  <a class="section" href="/">{{ __('Home') }}</a>
			  <i class="right chevron icon divider"></i>
			  <a class="section" href="{{ category_url($product->category->slug) }}">{{ $product->category->name }}</a>
			  <i class="right chevron icon divider"></i>
			  <div class="active section">{{ $product->name }}</div>
			</div>
			
			<div class="header">
				<div class="content">
					<div class="title">
						{!! $product->name !!}
					</div>
					@if($product->purchased && \Auth::check())
					<a class="ui button large w-100 rounded-corner mx-0 teal basic mb-1" 
							href="{{ route('home.download', ['type' => 'file', 'order_id' => $product->order_id, 'user_id' => Auth::id(), 'item_id' => $product->id]) }}"
						>{{ __('Download') }}</a>
					@endif
					<div class="summary">{!! shorten_str($product->short_description, 200) !!}</div>

					@if(config('app.realtime_views.product.enabled'))
					<div class="realtime-product-views"><span>@{{ realtimeViews.product }}</span>@{{ __('Users are currently viewing this item.') }}</div>
					@endif

					@if(!$product->valid_subscription && !$product->for_subscriptions && !out_of_stock($product))
					<div class="price-stock">
						<div class="price-wrapper {{ ($product->product_prices[$product->license_id]['has_promo'] ?? null) ? 'has-promo' : '' }}">
							@if($product->product_prices[$product->license_id]['is_free'])
							<div class="price default">{{ __('Free') }}</div>
							@elseif($product->product_prices[$product->license_id]['has_promo'])
							<div class="price promo">{{ price($product->product_prices[$product->license_id]['promo_price']) }}</div>
							<div class="price default">{{ price($product->product_prices[$product->license_id]['price']) }}</div>
							@else
							<div class="price default">{{ price($product->product_prices[$product->license_id]['price']) }}</div>
							@endif
		    		</div>

		    		@if(!is_null($product->stock))
		    		<div class="stock">{{ __(':count items left', ['count' => $product->stock])}}</div>
		    		@endif
	    		</div>

	    		<div class="time-counter">
						<span class="text">{{ __('Ends in') }}</span>
						<span class="time"></span>
					</div>

					<div class="price-container">
						@if(!$product->affiliate_link)
							<div class="ui fluid large floating circular button dropdown selection licenses {{ count($product->product_prices) <= 1 ? 'd-none disabled' : '' }}" :class="{'d-none': !itemHasPaidLicense()}">
								<input type="hidden" name="license_id" @change="setPrice">
								@if(count($product->product_prices) > 1)
								<i class="dropdown icon"></i>
								@endif
								<div class="text">{{ __(mb_ucfirst($product->license_name)) }}</div>
								<div class="menu">
									@foreach($product->product_prices as $product_price)
									<div class="item" data-value="{{ $product_price['license_id'] }}">{{ $product_price['license_name'] }}</div>
									@endforeach
								</div>
							</div>

							@if(config('payments.enable_add_to_cart'))
							<div class="item cart-action ui dropdown circular button selection action {{ count($product->product_prices) == 1 ? 'single' : '' }}">
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
								<a class="item cart-action ui large circular button action" @click="buyNow(product)">{{ __('Buy now') }}</a>
								@else
								<a class="item cart-action ui large circular button action" 
									href="{{ route('home.download', ['type' => 'file', 'order_id' => rand(100,9999), 'user_id' => Auth::id() ?? rand(100,9999), 'item_id' => $product->id]) }}"
								>{{ __('Download') }}</a>
								@endif
							@endif
						@else
							<a class="ui item button large rounded-corner mx-0 black basic" href="{{ $product->affiliate_link }}" target="_blank">{{ __('Buy now') }}</a>
						@endif
					</div>
					@endif

					@if($product->valid_subscription && !out_of_stock($product) && \Auth::check())
					<div class="price-container">
						<a class="item ui large circular button action" 
							href="{{ route('home.download', ['type' => 'file', 'order_id' => $product->transaction_id, 'user_id' => Auth::id(), 'item_id' => $product->id]) }}"
						>{{ __('Download') }}</a>
					</div>
					@endif

					@if(out_of_stock($product))
					<div class="ui message fluid out-of-stock">{{ __('This product is out of stock.') }}</div>
					@endif

					@if(config('app.subscriptions.enabled') && $subscriptions->count() && !out_of_stock($product))
					<div class="product-subscriptions">
						<p><i class="angle right icon"></i>{{ __('This product can be downloaded with the following subscriptions') }} :</p>
						<div class="items">
							@foreach($subscriptions as $subscription)
							<a href="{{ pricing_plan_url($subscription) }}" data-variation="small" data-variation="wide" data-inverted="" data-tooltip="{{ price($subscription->price) }} {{ $subscription->days ? " / {$subscription->days} ".__('Days') : "" }}" class="item">{{ $subscription->name }}</a>
							@endforeach
						</div>
					</div>
					@endif
				</div>

				<div class="image {{ $product->preview_type }}">
					@if($product->preview_url)
					<a href="{{ $product->preview_url }}" target="_blank" class="preview-url">{{ __('Preview') }}</a>
					@endif

					<div class="ui inverted dimmer">
				    <div class="ui text loader">{{ __('Loading') }}</div>
				  </div>

				  <div class="cover">
						<img src="/storage/covers/{{ $product->cover }}">

						@if($product->preview_is('audio'))
						<div class="player" data-type="audio" data-ready="false">
							<audio controls="" src="{{ isUrl($product->preview) ? $product->preview : asset_("storage/previews/{$product->preview}") }}" class="d-none"></audio>
							<div class="controls">
								<div class="play"><img src="/assets/images/pause.png"></div>
								<div class="wave"><span class="time"></span></div>
								<div class="stop"><img src="/assets/images/stop.png"></div>
							</div>
						</div>
						@elseif($product->preview_is('video'))
						<div data-src="{{ preview_link($product) }}" class="video">
							<img src="/assets/images/play-2.png">		
						</div>
						@endif
					</div>

					@if($product->screenshots)
					<div class="screenshots">
						@foreach($product->screenshots ?? [] as $screenshot)
						<a class="item" style="background-image: url('{{ $screenshot }}');"></a>
						@endforeach
					</div>

					<div class="ui modal modal-screenshots">
						<div class="content">
							<img src="">
						</div>
					</div>
					@endif
				</div>
			</div>

			<div class="body">
				<div class="ui secondary menu fluid">
					@if(config('app.show_streaming_player') && (auth_is_admin() || $product->purchased || $product->valid_subscription) && itemHasVideo($product))
					<a class="item" data-tab="streaming">{{ __('Streaming') }}</a>
					@endif
					
					@if($product->overview)
					<a class="item active" data-tab="description">{{ __('Description') }}</a>
					@endif
					
					@if($product->table_of_contents)
					<a class="item" data-tab="table_of_contents">{{ __('Table of contents') }}</a>
					@endif
					
					@if($product->hidden_content && (auth_is_admin() || $product->purchased || $product->valid_subscription) && itemHasVideo($product))
					<a class="item" data-tab="hidden_content">{{ __('Premium content') }}</a>
					@endif
					
					@if(config('app.enable_comments'))
					<a class="item" data-tab="comments">{{ __('Comments') }}</a>
					@endif
					
					@if(config('app.enable_reviews'))
					<a class="item" data-tab="reviews">{{ __('Reviews') }}</a>
					@endif
					
					@if($product->faq)
					<a class="item" data-tab="faq">{{ __('FAQ') }}</a>
					@endif
				</div>

				<div class="tabs">
					@if(config('app.show_streaming_player') && (auth_is_admin() || $product->purchased || $product->valid_subscription) && itemHasVideo($product))
					<div class="content item" data-tab="streaming">
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

					@if($product->overview)
					<div class="content item active" data-tab="description">
						{!! $product->overview !!}
					</div>
					@endif

					@if($product->table_of_contents)
					<div class="content item" data-tab="table_of_contents">
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

					@if($product->hidden_content && ($product->purchased || $product->valid_subscription))
					<div class="content item" data-tab="hidden_content">
						{!! $product->hidden_content !!}
					</div>
					@endif

					@if(config('app.enable_comments'))
					<div class="content item" data-tab="comments">
						<form class="comment-form ui big form" action="{{ url()->full() }}" method="POST">
							@csrf
							<input type="hidden" name="redirect_url" value="{{ url()->current() }}?tab=comments">
							<input type="hidden" name="type" value="comments" class="d-none">
							<input type="hidden" name="edit_comment_id" :value="commentId" class="d-none">
							
							@if(config('app.enable_subcomments'))
							<input type="hidden" name="comment_id" :value="replyTo.commentId" class="d-none">
							@endif

							<div class="field">
								<label>
									{{ __('Post a comment') }}
									@if(config('app.enable_subcomments'))
									<div class="reply-to" v-if="replyTo.userName !== null && commentId == null">
										@{{ replyTo.userName }}
										<i class="delete link icon mr-0" @click="resetReplyTo"></i>
									</div>
									@endif
									<div class="edit-comment" v-if="replyTo.userName == null && commentId !== null">
										@{{ __('Edit comment') }}
										<i class="delete link icon mr-0" @click="resetEditCommentId"></i>
									</div>
								</label>
								<textarea name="comment" rows="3" placeholder="{{ __('Your comment') }}..."></textarea>
								<button class="ui button">{{ __('Submit') }}</button>
							</div>
						</form>

						<div class="comments">
							@foreach($comments as $comment)
							<div class="comment main" id="cmt-{{ $comment->id }}">
								<div class="header">
									<img src="{{ asset_("storage/avatars/".$comment->avatar) }}" alt="{{ $comment->name ?? explode('@', $comment->email, 2)[0] }}">
									<div class="content">
										<div class="name">{{ $comment->name ?? explode('@', $comment->email, 2)[0] }}</div>
										<div class="date">
											{{ format_date($comment->updated_at, 'Y-m-d H:i:s') }}
											@if($comment->created_at != $comment->updated_at)
											<span>{{ __('Edited') }}</span>
											@endif
										</div>
									</div>
									<div class="ui dropdown default actions">
										<div class="default"><img src="/assets/images/more.png"></div>
										<div class="menu">
											<a class="item copy" data-link="{{ route('home.product', ['id' => $product->id, 'slug' => $product->slug, 'tab' => 'comments']) }}#cmt-{{ $comment->id }}">{{ __('Copy link') }}</a>
											@auth
											@if($comment->user_id == \Auth::id())
											<a class="item edit" @click="setEditCommentId({{ $comment->id }}, $event)">{{ __('Edit') }}</a>
											@if(config('app.can_delete_own_comments'))
											<a class="item delete" @click="deleteComment('{{ route('delete_comment', ['id' => $comment->id, 'redirect' => url()->current().'?tab=comments']) }}')">
												{{ __('Delete') }}
											</a>
											@endif
											@endif
											@endauth
										</div>
									</div>
								</div>

								<div class="body">
									{!! nl2br($comment->body) !!}
								</div>

								<div class="footer">
									@if(config('app.enable_reactions_on_comments'))
										<div class="reactions" data-item_id="{{ $comment->id }}" data-item_type="comment">
											<img src="/assets/images/neutral-face.png">
											<div class="popup">
												<div class="items">
										    	<a class="action" title="Like" data-reaction="like" style="background-image: url('{{ asset_('assets/images/reactions/like.png') }}')"></a>
										    	<a class="action" title="Love" data-reaction="love" style="background-image: url('{{ asset_('assets/images/reactions/love.png') }}')"></a>
										    	<a class="action" title="Funny" data-reaction="funny" style="background-image: url('{{ asset_('assets/images/reactions/funny.png') }}')"></a>
										    	<a class="action" title="Wow" data-reaction="wow" style="background-image: url('{{ asset_('assets/images/reactions/wow.png') }}')"></a>
										    	<a class="action" title="Sad" data-reaction="sad" style="background-image: url('{{ asset_('assets/images/reactions/sad.png') }}')"></a>
										    	<a class="action" title="Angry" data-reaction="angry" style="background-image: url('{{ asset_('assets/images/reactions/angry.png') }}')"></a>
										    </div>
									    </div>
										</div>

										@if(count($comment->reactions ?? []))
										<div class="saved-reactions" data-item_id="{{ $comment->id }}" data-item_type="comment">
											@foreach($comment->reactions ?? [] as $name => $count)
											<div class="reaction" data-reaction="{{ $name }}" data-tooltip="{{ $count }}" data-inverted="">
												<img src="{{ asset_("assets/images/reactions/{$name}.png") }}">
												<span class="count">{{ $count }}</span>
											</div>
											@endforeach
										</div>
										@endif
									@endif

									@if(config('app.enable_subcomments'))
										<div class="reply" @click="setReplyTo('{{ $comment->name ?? explode('@', $comment->email, 2)[0] }}', {{ $comment->id }})">
											{{ __('Reply') }}
										</div>
									@endif
								</div>
							</div>

							@if($comment->count() && config('app.enable_subcomments'))
							<div class="subcomments">
								@foreach($comment->children as $child)
								<div class="comment sub" id="cmt-{{ $child->id }}">
										<div class="header">
											<img src="{{ asset_("storage/avatars/".$child->avatar) }}" alt="{{ $child->name ?? explode('@', $child->email, 2)[0] }}">
											<div class="content">
												<div class="name">{{ $child->name ?? explode('@', $child->email, 2)[0] }}</div>
												<div class="date">
													{{ $child->updated_at }}
													@if($child->created_at != $child->updated_at)
													<span>{{ __('Edited') }}</span>
													@endif
												</div>
											</div>
											<div class="ui dropdown default actions">
												<div class="default"><img src="/assets/images/more.png"></div>
												<div class="menu">
													<a class="item copy" data-link="{{ route('home.product', ['id' => $product->id, 'slug' => $product->slug, 'tab' => 'comments']) }}#cmt-{{ $child->id }}">{{ __('Copy link') }}</a>
													@auth
													@if($child->user_id == \Auth::id())
													<a class="item edit" @click="setEditCommentId({{ $child->id }}, $event)">{{ __('Edit') }}</a>
													@if(config('app.can_delete_own_comments'))
													<a class="item delete" @click="deleteComment('{{ route('delete_comment', ['id' => $child->id, 'redirect' => url()->current().'?tab=comments']) }}')">
														{{ __('Delete') }}
													</a>
													@endif
													@endif
													@endauth
												</div>
											</div>
										</div>
										<div class="body">
											{!! nl2br($child->body) !!}
										</div>
										<div class="footer">
											@if(config('app.enable_reactions_on_comments'))
											<div class="reactions" data-item_id="{{ $child->id }}" data-item_type="comment">
												<img src="/assets/images/neutral-face.png">
												<div class="popup">
													<div class="items">
											    	<a class="action" title="Like" data-reaction="like" style="background-image: url('{{ asset_('assets/images/reactions/like.png') }}')"></a>
											    	<a class="action" title="Love" data-reaction="love" style="background-image: url('{{ asset_('assets/images/reactions/love.png') }}')"></a>
											    	<a class="action" title="Funny" data-reaction="funny" style="background-image: url('{{ asset_('assets/images/reactions/funny.png') }}')"></a>
											    	<a class="action" title="Wow" data-reaction="wow" style="background-image: url('{{ asset_('assets/images/reactions/wow.png') }}')"></a>
											    	<a class="action" title="Sad" data-reaction="sad" style="background-image: url('{{ asset_('assets/images/reactions/sad.png') }}')"></a>
											    	<a class="action" title="Angry" data-reaction="angry" style="background-image: url('{{ asset_('assets/images/reactions/angry.png') }}')"></a>
											    </div>
										    </div>
											</div>

											@if(count($child->reactions ?? []))
											<div class="saved-reactions" data-item_id="{{ $child->id }}" data-item_type="comment">
												@foreach($child->reactions ?? [] as $name => $count)
												<div class="reaction" data-reaction="{{ $name }}" data-tooltip="{{ $count }}" data-inverted="">
													<img src="{{ asset_("assets/images/reactions/{$name}.png") }}">
													<span class="count">{{ $count }}</span>
												</div>
												@endforeach
											</div>
											@endif
											@endif

											@if(config('app.enable_subcomments'))
											<div class="reply" @click="setReplyTo('{{ $child->name ?? explode('@', $child->email, 2)[0] }}', {{ $comment->id }})">
												{{ __('Reply') }}
											</div>
											@endif
										</div>
									</div>
								@endforeach
							</div>
							@endif
							@endforeach
						</div>
					</div>
					@endif

					@if(config('app.enable_reviews'))
					<div class="content item" data-tab="reviews">
						@if($product->purchased || $product->valid_subscription)
						<form class="review-form ui big form" action="{{ url()->full() }}" method="POST" v-if="!itemReviewed || (itemReviewed && reviewId !== null)">
							@csrf
							<input type="hidden" name="redirect_url" value="{{ url()->current() }}?tab=reviews">
							<input type="hidden" name="type" value="reviews" class="d-none">
							<input type="hidden" name="edit_review_id" :value="reviewId" class="d-none">

							<div class="field">
								<label>
									{{ __('Post a review') }}
									<div class="edit-review" v-if="reviewId !== null">
										@{{ __('Edit review') }}
										<i class="delete link icon mr-0" @click="resetEditReviewId"></i>
									</div>
								</label>

								<textarea name="review" rows="3" placeholder="{{ __('Your review') }}..."></textarea>
								<div class="actions">
									<button class="ui button">{{ __('Submit') }}</button>
									<div class="ui star rating" data-max-rating="5"></div>
									<input type="hidden" name="rating" class="d-none">
								</div>
							</div>
						</form>
						@endif

						<div class="reviews">
							@foreach($reviews as $review)
							<div class="review main" id="rev-{{ $review->id }}">
								<div class="header">
									<img src="{{ asset_("storage/avatars/".$review->avatar) }}" alt="{{ $review->name ?? explode('@', $review->email, 2)[0] }}">
									<div class="content">
										<div class="name">{{ $review->name ?? explode('@', $review->email, 2)[0] }}</div>
										<div class="date">
											{{ $review->updated_at }}
											@if($review->created_at != $review->updated_at)
											<span>{{ __('Edited') }}</span>
											@endif
										</div>
									</div>
									<div class="ui dropdown default actions">
										<div class="default"><img src="/assets/images/more.png"></div>
										<div class="menu">
											<a class="item copy" data-link="{{ route('home.product', ['id' => $product->id, 'slug' => $product->slug, 'tab' => 'reviews']) }}#rev-{{ $review->id }}">{{ __('Copy link') }}</a>
											@auth
											@if($review->user_id == \Auth::id())
											<a class="item edit" @click="setEditReviewId({{ $review->id }}, $event)">{{ __('Edit') }}</a>
											@if(config('app.can_delete_own_comments'))
											<a class="item delete" @click="deleteReview('{{ route('delete_review', ['id' => $review->id, 'redirect' => url()->current().'?tab=reviews']) }}')">
												{{ __('Delete') }}
											</a>
											@endif
											@endif
											@endauth
										</div>
									</div>
								</div>
								<div class="body">
									<div class="rating" data-rating="{{ $review->rating }}">
										{!! item_rating($review->rating) !!}
									</div>
									<div class="content">
										{!! nl2br($review->content) !!}
									</div>
								</div>
							</div>
							@endforeach
						</div>
					</div>
					@endif

					@if($product->faq)
					<div class="content item" data-tab="faq">
						<div class="items">
							@foreach($product->faq as $qa)
							<div class="item">
								<div class="question"><span>{{ __('Q') }}</span>. {{ $qa->question }}</div>
								<div class="answer"><span>{{ __('A') }}</span>. {{ $qa->answer }}</div>
							</div>
							@endforeach
						</div>
					</div>
					@endif

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
										<span class="avatar"><img loading="lazy" :src="'/storage/avatars/' + user.user_avatar" class="ui avatar image"></span>
										<span class="text">@{{ user.user_name }}</span>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="right-side">
			<div class="extra">
				<div class="share ui button dropdown nothing hidden">
					<div class="text">
						<span><img src="/assets/images/share.png"></span>
						{{ __('Share') }}
					</div>
					<div class="menu">
						<a target="_blank" href="{{ share_link('twitter', $product->permalink ?? null) }}" class="item"><img src="/assets/images/social/twitter.png">{{ __('Twitter') }}</a>
						<a target="_blank" href="{{ share_link('tumblr', $product->permalink ?? null) }}" class="item"><img src="/assets/images/social/tumblr.png">{{ __('Tumblr') }}</a>
						<a target="_blank" href="{{ share_link('vk', $product->permalink ?? null) }}" class="item"><img src="/assets/images/social/vkontakte.png">{{ __('vKontakte') }}</a>
						<a target="_blank" href="{{ share_link('pinterest', $product->permalink ?? null) }}" class="item"><img src="/assets/images/social/pinterest.png">{{ __('Pinterest') }}</a>
						<a target="_blank" href="{{ share_link('facebook', $product->permalink ?? null) }}" class="item"><img src="/assets/images/social/facebook.png">{{ __('Facebook') }}</a>
						<a target="_blank" href="{{ share_link('linkedin', $product->permalink ?? null) }}" class="item"><img src="/assets/images/social/linkedin.png">{{ __('Linkedin') }}</a>
						<a target="_blank" href="{{ share_link('reddit', $product->permalink ?? null, $product->name) }}" class="item"><img src="/assets/images/social/reddit.png">{{ __('Reddit') }}</a>
						<a target="_blank" href="{{ share_link('okru', $product->permalink ?? null) }}" class="item"><img src="/assets/images/social/okru.png">{{ __('Ok.ru') }}</a>
						<a target="_blank" href="{{ share_link('skype', $product->permalink ?? null, $product->name) }}" class="item"><img src="/assets/images/social/skype.png">{{ __('Skype') }}</a>
						<a target="_blank" href="{{ share_link('telegram', $product->permalink ?? null, $product->name) }}" class="item"><img src="/assets/images/social/telegram.png">{{ __('Telegram') }}</a>
						<a target="_blank" href="{{ share_link('whatsapp', $product->permalink ?? null, $product->name) }}" class="item"><img src="/assets/images/social/whatsapp.png">{{ __('Whatsapp') }}</a>
					</div>
				</div>
				<div class="like ui button" @click="collectionToggleItem($event, {{ $product->id }})" :class="{active: itemInCollection({{ $product->id }})}">
					<span><img src="/assets/images/like-2.png"></span>
					<div class="add">{{ __('Like') }}</div>
					<div class="added">{{ __('Unlike') }}</div>
				</div>
			</div>	

			<div class="specs">
				<div class="item category">
					<span>{{ __('Category') }}</span>
					<span><a href="{{ category_url($product->category->slug) }}">{{ $product->category->name }}</a></span>
				</div>
				@if(config('app.show_rating.product_page'))
				<div class="item rating">
					<span>{{ __('Rating') }}</span>
					<span><a class="mr-1-qt">{{ $product->rating ?? '0' }}</a> / 5</span>
				</div>
				@endif
				@if($product->version)
				<div class="item">
					<span>{{ __('Version') }}</span>
					<span>{{ $product->version }}</span>
				</div>
				@endif
				@if($product->included_files)
				<div class="item">
					<span>{{ __('Included files') }}</span>
					<span>{{ $product->included_files }}</span>
				</div>
				@endif
				@if($product->compatible_browsers)
				<div class="item">
					<span>{{ __('Compatible browsers') }}</span>
					<span>{{ $product->compatible_browsers }}</span>
				</div>
				@endif
				@if($product->release_date)
				<div class="item">
					<span>{{ __('Release date') }}</span>
					<span>{{ $product->release_date }}</span>
				</div>
				@endif
				@if($product->last_update)
				<div class="item">
					<span>{{ __('Latest update') }}</span>
					<span>{{ $product->last_update }}</span>
				</div>
				@endif
				@if($product->stock)
				<div class="item">
					<span>{{ __('Quantity') }}</span>
					<span>{{ $product->stock }}</span>
				</div>
				@endif
				@if($product->authors)
				<div class="item">
					<span>{{ __('Authors') }}</span>
					<span>{{ $product->authors }}</span>
				</div>
				@endif
				@if($product->pages)
				<div class="item">
					<span>{{ __('Pages') }}</span>
					<span>{{ $product->pages }}</span>
				</div>
				@endif
				@if($product->words)
				<div class="item">
					<span>{{ __('Words') }}</span>
					<span>{{ $product->words }}</span>
				</div>
				@endif
				@if($product->language)
				<div class="item">
					<span>{{ __('Language') }}</span>
					<span>{{ $product->language }}</span>
				</div>
				@endif
				@if($product->bpm)
				<div class="item">
					<span>{{ __('BPM') }}</span>
					<span>{{ $product->bpm }}</span>
				</div>
				@endif
				@if($product->bit_rate)
				<div class="item">
					<span>{{ __('Bit rate') }}</span>
					<span>{{ $product->bit_rate }}</span>
				</div>
				@endif
				@if($product->label)
				<div class="item">
					<span>{{ __('Label') }}</span>
					<span>{{ $product->label }}</span>
				</div>
				@endif
				<div class="item">
					<span>{{ __('Comments') }}</span>
					<span>{{ $product->comments_count }}</span>
				</div>
				@if($product->high_resolution)
				<div class="item">
					<span>{{ __('High resolution') }}</span>
					<span>{{ $product->high_resolution ? 'Yes' : 'No' }}</span>
				</div>
				@endif
				@if(config('app.show_sales.product_page'))
				<div class="item">
					<span>{{ __('Sales') }}</span>
					<span>{{ $product->sales }}</span>
				</div>
				@endif
			</div>
		</div>
	</div>
</div>
@endsection