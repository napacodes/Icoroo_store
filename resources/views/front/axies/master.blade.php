<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ config('app.fullwide') ? 'fullwide' : '' }}">
	<head>
		<meta charset="UTF-8">
		<meta name="language" content="{{ str_replace('_', '-', app()->getLocale()) }}">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link rel="icon" href="{{ asset_("storage/images/".config('app.favicon'))}}">
		{{-- <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests"> --}}

		{!! config('app.analytics_code') !!}
		{!! place_ad('popup_ad') !!}
		{!! place_ad('auto_ad') !!}

		{{-- Search engines verification --}}
		@if(config('app.site_verification'))
		{!! config('app.site_verification') !!}
		@endif

		<title>{!! config('meta_data.title') !!}</title>

		<link rel="canonical" href="{{ config('meta_data.url') }}">

		<meta name="route" content="{{ \Route::currentRouteName() }}">

		<meta property="og:site_name" name="site_name" content="{{ config('meta_data.name') }}">
		<meta property="og:title" name="title" itemprop="title" content="{!! config('meta_data.title') !!}">
		<meta property="og:type" name="type" itemprop="type" content="Website">
		<meta property="og:url" name="url" itemprop="url" content="{{ config('meta_data.url') }}">
		<meta property="og:description" name="description" itemprop="description" content="{!! config('meta_data.description') !!}">
		<meta property="og:image" name="og:image" itemprop="image" content="{{ config('meta_data.image') }}">

		<meta property="fb:app_id" content="{{ config('app.fb_app_id') }}">
		<meta name="og:image:width" content="590">
		<meta name="og:image:height" content="auto">

		@if(config('app.json_ld'))
		<script type="application/ld+json">
		{!! @json_encode(config('json_ld', []), JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}
		</script>
		@endif

		{{-- CSRF Token --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- translations --}}
    <script type="application/javascript" src="/translations.js"></script>
		
		{{-- jQuery --}}  
		<script type="application/javascript" src="/assets/jquery-3.6.0.min.js"></script>
		
		{{-- Js-Cookie --}}
		<script type="application/javascript" src="/assets/js.cookie.min.js"></script>

		{{-- JQuery-UI --}}
		<script type="application/javascript" src="/assets/jquery-ui-1.13.1/jquery-ui.min.js"></script>
		<link rel="stylesheet" href="/assets/jquery-ui-1.13.1/jquery-ui.min.css">
		
    {{-- Semantic-UI --}}
    <link rel="stylesheet" href="/assets/semantic-ui/semantic.min.2.4.2-{{ locale_direction() }}.css">
    <script type="application/javascript" src="/assets/semantic-ui/semantic.min.2.4.2.js"></script>

	  {{-- Spacing CSS --}}
		<link rel="stylesheet" href="/assets/css-spacing/spacing-{{ locale_direction() }}.css">

		{{-- App CSS --}}
		<link id="app-css" rel="stylesheet" href="/assets/front/axies-{{ locale_direction() }}.css">

		{{-- Query string --}}
		<script type="application/javascript" src="/assets/query-string.min.js"></script>

		{{-- Base64 Encode / Decode --}}
		<script type="application/javascript" src="/assets/base64.min.js"></script>

		<style>
			{!! load_font() !!}
			
			@if(config('app.top_cover'))
				#menu-cover {
					background-image: url('{{ asset_('storage/images/'.config('app.top_cover')) }}');

					@if(config('app.axies_top_cover_mask'))
					-webkit-mask-image: url('{{ asset_('storage/images/'.config('app.axies_top_cover_mask')) }}');
			    -webkit-mask-position: bottom center;
			    -webkit-mask-size: cover;
					@else
					padding: 0;
					@endif
				}
			@endif
			
			@if(config('app.cookie.background'))
			#cookie .content .text {
				background: {{ config('app.cookie.background') }} !important;
			}
			@endif

			@if(config('app.cookie.color'))
			#cookie .content .text, #cookie .content .text * {
				color: {{ config('app.cookie.color') }} !important;
			}
			@endif

			@if(config('app.cookie.button_bg'))
			#cookie .action button {
				background: {{ config('app.cookie.button_bg') }} !important;
			}
			@endif
		</style>

		<script type="application/javascript" src="/props.js?t={{ time() }}"></script>

		@yield('additional_head_tags')
	</head>

	<body dir="{{ locale_direction() }}">
		@if(config('app.color_cursor'))
		<div id="cursor"></div>
		@endif
		
		<div class="ui main fluid container {{ str_ireplace('.', '_', \Route::currentRouteName()) }}" id="app" vhidden>

			<section id="left-section">
				<a href="/" class="header logo"><img src="{{ asset_("storage/images/".config('app.logo')) }}"></a>
				
				<div class="menu">
					<form class="ui action fluid input item" action="{{ route('home.products.q') }}" method="GET">
					  <input type="text" placeholder="Search..." name="q" required value="{{ request()->query('q') }}">
					  <button class="ui icon yellow button"><i class="search icon mx-0"></i></button>
					</form>

					<div class="main">
						@guest
						<a class="item parent">
							<div>
								<span class="image"><img src="{{ asset_("assets/images/axies/user.png") }}"></span>
								<span class="text">{{ __('Account') }}</span>
							</div>
						</a>
						@else
						@if(auth_is_admin())
						<a class="item parent">
							<div>
								<span class="image"><img src="{{ asset_("assets/images/axies/user.png") }}"></span>
								<span class="text">{{ __('Administration') }}</span>
							</div>
						</a>
						@else
						<a class="item parent" href="{{ route('home.profile') }}">
							<div>
								<span class="image"><img src="{{ asset_("assets/images/axies/user.png") }}"></span>
								<span class="text">{{ __('Profile') }}</span>
							</div>
						</a>
						@endif
						@endguest
						<a href="{{ route('home') }}" class="item parent">
							<div>
								<span class="image"><img src="{{ asset_("assets/images/axies/home.png") }}"></span>
								<span class="text">{{ __('Home') }}</span>
							</div>
						</a>
						<div class="item parent list">
							<div>
								<span class="image"><img src="{{ asset_("assets/images/axies/categories.png") }}"></span>
								<span class="text">{{ __('Categories') }}</span>
							</div>
						</div>
						<div class="item list">
							@foreach(config('categories.category_parents', []) as $category)
							<a href="{{ route('home.products.category', ['category_slug' => $category->slug]) }}" class="item">{{ $category->name }}</a>
							@endforeach
						</div>
						@if(config('app.subscriptions.enabled') == 1)
						<a href="{{ route('home.subscriptions') }}" class="item parent">
							<div>
								<span class="image"><img src="{{ asset_("assets/images/axies/pricing.png") }}"></span>
								<span class="text">{{ __('Pricing') }}</span>
							</div>
						</a>
						@endif
						@if('app.prepaid_credits.enabled')
						<a href="{{ route('home.add_prepaid_credits') }}" class="item parent list">
							<div>
								<span class="image"><img src="{{ asset_("assets/images/axies/credits.png") }}"></span>
								<span class="text">{{ __('Prepaid credits') }}</span>
							</div>
						</a>
						@endif
						@if(config('app.blog.enabled') == 1)
						<a href="{{ route('home.blog') }}" class="item parent">
							<div>
								<span class="image"><img src="{{ asset_("assets/images/axies/blog.png") }}"></span>
								<span class="text">{{ __('Blog') }}</span>
							</div>
						</a>
						@endif
						<a href="{{ route('home.favorites') }}" class="item parent">
							<div>
								<span class="image"><img src="{{ asset_("assets/images/axies/collection.png") }}"></span>
								<span class="text">{{ __('Collection') }}</span>
							</div>
						</a>
						<a class="item parent" href="{{ route('home.page', ['slug' => 'privacy-policy']) }}">
							<div>
								<span class="image"><img src="{{ asset_("assets/images/axies/privacy.png") }}"></span>
								<span class="text">{{ __('Privacy policy') }}</span>
							</div>
						</a>
						<a class="item parent" href="{{ route('home.page', ['slug' => 'terms-and-conditions']) }}">
							<div>
								<span class="image"><img src="{{ asset_("assets/images/axies/terms.png") }}"></span>
								<span class="text">{{ __('Terms and conditions') }}</span>
							</div>
						</a>
						<a class="item parent" href="{{ route('home.support') }}">
							<div>
								<span class="image"><img src="{{ asset_("assets/images/axies/support.png") }}"></span>
								<span class="text">{{ __('Support') }}</span>
							</div>
						</a>
						@if(count(config('langs') ?? []) > 1)
						<div class="item parent list">
							<div>
								<span class="image"><img src="{{ asset_("assets/images/axies/languages.png") }}"></span>
								<span class="text">{{ __('Languages') }}</span>
							</div>
						</div>
						<div class="item list">
							@foreach(\LaravelLocalization::getSupportedLocales() as $locale_code => $supported_locale)
							<a @click="setLocale('{{ $locale_code }}')" class="item capitalize">{{ $supported_locale['native'] ?? '' }}</a>
							@endforeach
						</div>
						@endif
						@if(count(config('payments.currencies') ?? []) > 1)
						<div class="item parent list">
							<div>
								<span class="image"><img src="{{ asset_("assets/images/axies/currency.png") }}"></span>
								<span class="text uppercase">{{ session('currency', config('payments.currency_code')) }}</span>
							</div>
						</div>
						<div class="item list">
							@foreach(config('payments.currencies') as $code => $currency)
							<a href="{{ route('set_currency', ['code' => $code, 'redirect' => url()->full()]) }}" class="item">{{ $code }}</a>
							@endforeach
						</div>
						@endif
					</div>

					<div class="footer">
						<div class="social">
							<a href="{{ config('app.tiktok') }}" class="button"><img src="{{ asset_('assets/images/social/tiktok.webp') }}"></a>
							<a href="{{ config('app.twitter') }}" class="button"><img src="{{ asset_('assets/images/social/twitter.webp') }}"></a>
							<a href="{{ config('app.youtube') }}" class="button"><img src="{{ asset_('assets/images/social/youtube.webp') }}"></a>
							<a href="{{ config('app.vk') }}" class="button"><img src="{{ asset_('assets/images/social/vkontakte.webp') }}"></a>
							<a href="{{ config('app.facebook') }}" class="button"><img src="{{ asset_('assets/images/social/facebook.webp') }}"></a>
						</div>
						<div class="copyright">
							{{ __(':name © :year all right reserved', ['name' => config('app.name'), 'year' => date('Y')]) }}
						</div>
					</div>
				</div>
			</section>

			<section id="right-section">
				<div id="top-menu">
					<div class="ui secondary menu">
						<a href="/" class="item header logo"><img src="{{ asset_("storage/images/".config('app.logo')) }}"></a>

						@if(config('app.blog.enabled') == 1)
						<a href="{{ route('home.blog') }}" class="item blog">{{ __('Blog') }}</a>
						@endif

						@if(config('app.subscriptions.enabled') == 1)
						<a href="{{ route('home.subscriptions') }}" class="item pricing">{{ __('Pricing') }}</a>
						@endif

						@if(config('app.prepaid_credits.enabled'))
						<a href="{{ route('home.add_prepaid_credits') }}" class="item credits capitalize">{{ __('Credits') }}</a>
						@endif

						<div class="item search">
							<form action="{{ route('home.products.q') }}" method="get" class="ui icon input">
								<input type="text" name="q" required placeholder="Search..." value="{{ request()->query('q') }}"> 
								<i class="search link icon"></i>
							</form> 
						</div>

						<div class="item ui dropdown hidden notifications">
						  <div class="default">
						  	<img src="/assets/images/axies/notifications.png"/>
						  	<span>({{ count(config('notifications', [])) }})</span>
						  </div>
						  <div class="left menu">
						  	<div class="container">
							  	@foreach(config('notifications', []) as $notif)
							    <a class="item" data-id="{{ $notif->id }}"
	                   data-href="{{ route('home.product', ['id' => $notif->product_id, 'slug' => $notif->slug . ($notif->for == 1 ? '?tab=comments' : ($notif->for == 2 ? '#reviews' : ''))]) }}">
	                  <div class="image" style="background-image: url('{{ $notif->for == 0 ? asset_("storage/covers/{$notif->image}") : asset_("storage/avatars/{$notif->image}") }}')"></div>
			              <div class="content">
			              	<div class="name">{!! __($notif->text, ['product_name' => "<strong>{$notif->name}</strong>"]) !!}</div> 
	                    <div class="date">{{ $notif->updated_at }}</div>
			              </div>
			            </a>
							    @endforeach
						    </div>

						    @if(config('notifications', []))
						    <a href="{{ route('home.notifications') }}" class="item all">{{ __('Notifications') }}</a>
						    @endif
						  </div>
						</div>

						@if(config('payments.enable_add_to_cart'))
						<div class="item ui dropdown hidden cart">
						  <div class="default"><img src="/assets/images/axies/cart.png"/><span>(@{{ cartItems }})</span></div>
						  <div class="left menu">
						  	<div class="container">
							    <div class="item" v-for="item in cart">
		                <a class="image" :style="'background-image: url('+ item.cover +')'" :href="item.url"></a>
		                <div class="content">
		                  <a class="name" :title="item.name" :href="item.url">@{{ item.name }}</a> 
	                    <div class="price">@{{ price(item.price, true) }}</div>
	                    <i class="trash alternate outline icon mx-0" :disabled="couponRes.status" @click="removeFromCart(item.id)"></i>
		                </div>
		              </div>
		            </div>

						    <a v-if="cartItems" href="{{ route('home.checkout') }}" class="item all">{{ __('Checkout') }}</a>
						  </div>
						</div>
						@endif

						@auth
						<div class="item ui dropdown user nothing mr-0">
						  <div class="default"><img src="{{ asset_("storage/avatars/".(request()->user()->avatar ?? "default.webp")) }}"></div>
						  <div class="left menu">
						  @if(auth_is_admin())
		            <a class="item" href="{{ route('admin') }}">
		              <i class="circle blue icon"></i>
		              {{ __('Administration') }}
		            </a>
		            <a class="item" href="{{ route('profile.edit') }}">
		                <i class="user outline icon"></i>
		                {{ __('Profile') }}
		            </a>
		            <a class="item" href="{{ route('transactions') }}">
		                <i class="shopping cart icon"></i>
		                {{ __('Transactions') }}
		            </a>
		            <a class="item" href="{{ route('settings', ['settings_name' => 'general']) }}">
		              <i class="cog icon"></i>
		              {{ __('Settings') }}
		            </a>
		            <a class="item" href="{{ route('products') }}">
		              <i class="file code outline icon"></i>
		              {{ __('Products') }}
		            </a>
		            <a class="item" href="{{ route('pages') }}">
		              <i class="sticky note outline icon"></i>
		              {{ __('Pages') }}
		            </a>
		            <a class="item" href="{{ route('posts') }}">
		              <i class="file alternate outline icon"></i>
		              {{ __('Posts') }}
		            </a>
		            <a class="item" href="{{ route('categories') }}">
		              <i class="tags icon"></i>
		              {{ __('Categories') }}
		            </a>
		            <a class="item" href="{{ route('faq') }}">
		              <i class="question circle icon"></i>
		              {{ __('Faq') }}
		            </a>
		            <a class="item" href="{{ route('support') }}">
		                <i class="comments outline icon"></i>
		                {{ __('Support') }}
		            </a>
		          @else
		            @if(auth_is_affiliate() || config('app.prepaid_credits.enabled') == 1)
		            @if(config('app.prepaid_credits.enabled') == 1)
		            <a class="item header earnings" href="{{ route('home.credits') }}">
		                {{ __('Credits : :value', ['value' => price(user_credits(), false)]) }}
		            </a>
		            @else
		            <div class="item header earnings">
		                {{ __('Credits : :value', ['value' => price(user_credits(), false)]) }}
		            </div>
		            @endif
		            @endif

		            <a class="item" href="{{ route('home.profile') }}">
		                <img src="{{ asset_('assets/images/icons-2/profile.png') }}">
		                {{ __('Profile') }}
		            </a>
		            <a class="item" href="{{ route('home.favorites') }}">
		                <img src="{{ asset_('assets/images/icons-2/collection.png') }}">
		                {{ __('Collection') }}
		            </a>
		            <a class="item" href="{{ route('home.notifications') }}">
		                <img src="{{ asset_('assets/images/icons-2/notifications.png') }}">
		                {{ __('Notifications') }}
		            </a>
		            <a class="item" href="{{ route('home.user_prepaid_credits') }}">
		                <img src="{{ asset_('assets/images/icons-2/credits.png') }}">
		                {{ __('Prepaid credits') }}
		            </a>
		            <a class="item" href="{{ route('home.user_subscriptions') }}">
		                <img src="{{ asset_('assets/images/icons-2/memberships.png') }}">
		                {{ __('Subscriptions') }}
		            </a>
		            <a class="item" href="{{ route('home.purchases') }}">
		                <img src="{{ asset_('assets/images/icons-2/purchases.png') }}">
		                {{ __('Purchases') }}
		            </a>
		            <a class="item" href="{{ route('home.invoices') }}">
		                <img src="{{ asset_('assets/images/icons-2/invoices.png') }}">
		                {{ __('Invoices') }}
		            </a>
		            <a class="item" href="{{ route('home.user_coupons') }}">
		                <img src="{{ asset_('assets/images/icons-2/purchases.png') }}">
		                {{ __('Coupons') }}
		            </a>
		          @endif

		          <div class="ui divider my-0"></div>

		          <a class="item logout w-100 mx-0" @click="logout">
		              <img src="{{ asset_('assets/images/icons-2/logout.png') }}">
		              {{ __('Sign out') }}
		          </a>
						  </div>
						</div>
						@else
						<a href="{{ route('login') }}" class="item">{{ __('Account') }}</a>
						@endif
						<div class="item mobile-menu-toggler">
							<i class="icon ellipsis horizontal mx-0"></i>
						</div>
					</div>
				</div>	

				@if(config('app.categories_on_homepage'))
				<div id="categories">
					<div class="wrapper">
						@php
							$categories_i = 0;
							$min_categories = config('app.fullwide') ? 10 : 7;
						@endphp
						@foreach(collect(config('categories.category_parents', []))->where('featured', 1) as $category)
							<a href="{{ route('home.products.category', ['category_slug' => $category->slug]) }}" class="item" 
								style="background-image: url('{{ $category->icon ? "/storage/icons/{$category->icon}" : "/storage/covers/{$category->cover}" }}');">
								<div>{{ $category->name }}</div>
							</a>
							<?php $categories_i++ ?>
							@foreach(collect(array_slice(shuffle_array(config("categories.category_children.{$category->id}", [])), 5))->where('featured', 1) as $subcategory)
							<a href="{{ route('home.products.category', $category->slug.'/'.$subcategory->slug) }}" class="item" 
								style="background-image: url('{{ $subcategory->icon ? "/storage/icons/{$subcategory->icon}" : "/storage/covers/{$subcategory->cover}" }}');">
								<div>{{ $subcategory->name }}</div>
							</a>
							<?php $categories_i++ ?>
							@endforeach
						@endforeach
						@if($categories_i < $min_categories)
						@for($k=0; $k<($min_categories-$categories_i); $k++)
						<div class="item"></div>
						@endfor
						@endif
					</div>
				</div>
				@endif

				@yield('top-panel')

				<div id="body">
					@yield('body')
				</div>

				<footer id="footer">
					@if(display_counters())
					<div class="counters ui four stackable doubling cards">
						@if(!is_null(config('app.counters.orders')))
						<div class="card fluid">
							<div class="content header">
								{{ __('Orders') }}
							</div>
							<div class="content count">
								<img src="{{ asset_('assets/images/orders.png') }}">
								<span>{{ cache('counters.orders', rand(500, 1000)) }}</span>
							</div>
						</div>
						@endif

						@if(!is_null(config('app.counters.products')))
						<div class="card fluid">
							<div class="content header">
								{{ __('Products') }}
							</div>
							<div class="content count">
								<img src="{{ asset_('assets/images/products.png') }}">
								<span>{{ cache('counters.products', rand(500, 1000)) }}</span>
							</div>
						</div>
						@endif

						@if(config('app.realtime_views.website.enabled') == 1 && !is_null(config('app.counters.online_users')))
						<div class="card fluid">
							<div class="content header">
								{{ __('Online users') }}
							</div>
							<div class="content count">
								<img src="{{ asset_('assets/images/online-users.png') }}">
								<span>@{{ realtimeViews.website }}</span>
							</div>
						</div>
						@endif

						@if(!is_null(config('app.counters.categories')))
						<div class="card fluid">
							<div class="content header">
								{{ __('Categories') }}
							</div>
							<div class="content count">
								<img src="{{ asset_('assets/images/categories.png') }}">
								<span>{{ cache('counters.categories', rand(500, 1000)) }}</span>
							</div>
						</div>
						@endif

						@if(!is_null(config('app.counters.affiliate_earnings')))
						<div class="card fluid">
							<div class="content header">
								{{ __('Affiliate earnings') }}
							</div>
							<div class="content count">
								<img src="{{ asset_('assets/images/affiliate_earnings.png') }}">
								<span>{{ cache('counters.affiliate_earnings', rand(500, 1000)) }}</span>
							</div>
						</div>
						@endif
					</div>
					@endif

					<form class="newsletter" action="{{ route('home.newsletter', ['redirect' => url()->current()]) }}" method="post">
						@if(session('newsletter_subscription_msg'))
						<div class="ui small message mx-auto compact white shadowless rounded-corner pr-2">
							<i class="close blue icon"></i>
							{{ session('newsletter_subscription_msg') }}
						</div>
						@endif

						<div class="wrapper">
							@csrf
							<div class="header">{{ __('Subscribe to our newsletter') }}</div>
							<div class="ui right action fluid input">
							  <input type="text" placeholder="Email..." name="email">
							  <button class="ui button">{{ __('Subscribe') }}</button>
							</div>
						</div>
					</form>
					<div class="data">
						<div class="header">{{ config('app.name') }}</div>
						<div class="description">{{ config('app.description') }}</div>
					</div>
					<div class="ui menu secondary">
						<a href="{{ route('home') }}" class="item">{{ __('Home') }}</a>
						<a href="{{ route('home.support') }}" class="item">{{ __('Support') }}</a>
						@if(config('app.blog.enabled') == 1)
						<a href="{{ route('home.blog') }}" class="item">{{ __('Blog') }}</a>
						@endif
						@if(auth_is_admin())
						<div class="item ui top dropdown templates">
							<div class="text uppercase">{{ __('Template') }}</div>

							<div class="left menu rounded-corner">
								<div class="header">{{ __('Templates') }}</div>
								@foreach($templates as $template)
								<a href="{{ route('set_template', ['template' => $template, 'redirect' => url()->full()]) }}" class="item capitalize">{{ ucfirst($template) }}</a>
								@endforeach
							</div>
						</div>
						@endif
						@if(count(config('langs') ?? []) > 1)
				    <div class="item ui top dropdown languages">
				      <div class="text capitalize">{{ __(config('laravellocalization.supportedLocales.'.session('locale', 'en').'.name')) }}</div>
				    
				      <div class="left menu rounded-corner">
				      	<div class="header">{{ __('Languages') }}</div>
				      	<div class="wrapper">
					        @foreach(\LaravelLocalization::getSupportedLocales() as $locale_code => $supported_locale)
					        <a class="item" @click="setLocale('{{ $locale_code }}')" data-value="{{ $locale_code }}">
					          {{ $supported_locale['native'] ?? '' }}
					        </a>
					        @endforeach
					      </div>
				      </div>
				    </div>
				    @endif
				    @if(count(config('payments.currencies') ?? []) > 1)
						<div class="item ui top dropdown currencies">
							<div class="text uppercase">{{ session('currency', config('payments.currency_code')) }}</div>

							<div class="left menu rounded-corner">
								<div class="header">{{ __('Currency') }}</div>
								<div class="wrapper">
									@foreach(config('payments.currencies') as $code => $currency)
									<a href="{{ route('set_currency', ['code' => $code, 'redirect' => url()->full()]) }}" class="item">{{ $code }}</a>
									@endforeach
								</div>
							</div>
						</div>
						@endif
						@if(config('affiliate.enabled') == 1)
						<a href="{{ route('home.affiliate') }}" class="item">{{ __('Affiliate Program') }}</a>
						@endif
						@foreach(config('pages', []) as $page)
						<a href="{{ route('home.page', ['slug' => $page['slug']]) }}" class="item">{{ __($page['name']) }}</a>
						@endforeach
					</div>

					@auth
					<form id="logout-form" action="{{ route('logout', ['redirect' => url()->full()]) }}" method="POST" class="d-none">@csrf</form>
					@endauth

					<form action="{{ route('set_locale') }}" method="post" class="d-none" id="set-locale">
						<input type="hidden" name="redirect" value="{{ url()->full() }}">
						<input type="hidden" name="locale" v-model="locale">
					</form>
				</footer>
			</section>

			<div class="ui modal video-player">
				<div class="content">
					<div class="player stopped" data-type="video" data-ready="false" data-full="false">
						<div class="ui inverted dimmer">
					    <div class="ui text loader">{{ __('Loading') }}</div>
					  </div>
						<video src="" type="video/mp4"></video>
						<div class="controls">
							<div class="play"><img src="/assets/images/pause.png"></div>
							<div class="wave"><span class="time"></span></div>
							<div class="stop"><img src="/assets/images/stop.png"></div>
						</div>
					</div>
				</div>	
			</div>

			@if(config('app.fake_purchases.enabled') == 1)
			<div id="recent-purchase-popup" v-if="Object.keys(recentPurchase).length">
			  <div class="content">
			    <div class="cover" :style="'background-image: url(' + recentPurchase.cover + ')'"></div>
			    <div class="details">
			    	<div class="header">
			    		<img class="avatar" :src="'/storage/profiles/' + recentPurchase.avatar">
			    		<div class="name">@{{ recentPurchase.name.shorten(30) }}</div>
			    	</div>
			    	<a :href="recentPurchase.url" class="item"><span>@{{ __('Just purchased') }}</span> "@{{ recentPurchase.item_name.shorten(30) }}"</a>
			    	<div class="price">@{{ recentPurchase.price }}</div>
			    </div>
			  </div>
			</div>
			@endif

			@include("components.user_message")
			@include("components.cookie")
		</div>

		@yield('pre_js')

		{{-- App JS --}}
		<script type="application/javascript" src="{{ asset_('assets/front/axies.js') }}"></script>
		
		{{-- Views counter   --}}
		<div id="realtime-views"></div>

		@if(session('user_message'))
		<script>
			'use strict';

			$(function()
			{
				app.showUserMessage("{!! session('user_message') !!}");
			})
		</script>
	  @endif

	  @if(config('chat.enabled'))
	  {!! config('chat.code') !!}
	  @endif

	  @if(config('app.js_css_code.frontend'))
		{!! config('app.js_css_code.frontend') !!}
		@endif

	  @yield('post_js')
	</body>
</html>