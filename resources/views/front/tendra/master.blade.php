{{-- TENDRA --}}

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
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
		<script type="application/javascript" src="{{ asset_('assets/jquery-3.6.0.min.js') }}"></script>
		
		{{-- Js-Cookie --}}
		<script type="application/javascript" src="{{ asset_('assets/js.cookie.min.js') }}"></script>

    {{-- Semantic-UI --}}
    <link rel="stylesheet" href="{{ asset_('assets/semantic-ui/semantic.min.2.4.2-'.locale_direction().'.css') }}">
    <script type="application/javascript" src="{{ asset_('assets/semantic-ui/semantic.min.2.4.2.js') }}"></script>

    {{-- Spacing CSS --}}
		<link rel="stylesheet" href="{{ asset_('assets/css-spacing/spacing-'.locale_direction().'.css') }}">

		{{-- App CSS --}}
		<link rel="stylesheet" href="{{ asset_('assets/front/tendra-'.locale_direction().'.css') }}">

		{{-- Query string --}}
		<script type="application/javascript" src="/assets/query-string.min.js"></script>

		{{-- Base64 Encode / Decode --}}
		<script type="application/javascript" src="/assets/base64.min.js"></script>

		<style>
			{!! load_font() !!}
			
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

			@if(config('app.product_card_cover_mask'))
			#body .card.product .header .cover-mask {
				display: block;
			  -webkit-mask-image: url(/assets/images/{{ config('app.product_card_cover_mask') }});
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

		<div class="ui main fluid container {{ route_slug() }}" id="app" vhidden>
			<div class="ui celled grid m-0 shadowless">

				{{-- Top Menu --}}
				<div class="row">
					<div class="ui unstackable secondary menu top attached {{ route_slug() }}" id="top-menu">
					  
					  <div class="wrapper">
					    <a class="item header logo" href="{{ route('home') }}">
					      <img class="ui image" src="{{ asset_("storage/images/".config('app.logo')) }}" alt="{{ config('app.name') }}">
					    </a>

					    <div class="right menu pr-1"> 
					    	@if(!route_is('home'))
					    	<div class="item search">
					    		<form method="get" action="{{ route('home.products.q') }}" class="ui large form">
			              <div class="ui icon input">
			                <input type="text" name="q" placeholder="{{ __('Search') }}...">
			                <i class="search link icon"></i>
			              </div>
			            </form>
					    	</div>
					    	@endif
					      
					      <div class="item ui dropdown categories">
					        <div class="toggler">
					          {{ __('Categories') }}
					        </div>

					        <div class="menu">
					          @foreach(config('categories.category_parents', []) as $category)
					          <a href="{{ route('home.products.category', ['category_slug' => $category->slug]) }}" class="item capitalize">
					            {{ $category->name }}
					          </a>
					          @endforeach
					        </div>
					      </div>
					      
					      @if(!auth_is_admin())
					      <a href="{{ route('home.favorites') }}" class="item collection" title="Collection">
					        {{ __('Collection') }}
					      </a>
					      @endif
					      
					      @if(config('app.prepaid_credits.enabled') == 1)
								<a href="{{ route('home.add_prepaid_credits') }}" class="item credits capitalize">{{ __('Credits') }}</a>
								@endif

					      @if(config('app.subscriptions.enabled') == 1)
					      <a href="{{ route('home.subscriptions') }}" class="item help">
					        {{ __('Pricing') }}
					      </a>
					      @endif
					      
					      @if(!auth_is_admin() && \Auth::check())
					      <div class="item notifications dropdown toggler">
					        <div><img src="/assets/images/notifications.png"><span v-cloak>({{ count(config('notifications', [])) }})</span></div>

					        <div class="menu">
					          <div>
					           
					            <div class="ui unstackable items">
					              @if(config('notifications'))
					              <div class="items-wrapper">
					                @foreach(config('notifications') as $notif)
					                <a class="item mx-0"
					                   data-id="{{ $notif->id }}"
					                   data-href="{{ route('home.product', ['id' => $notif->product_id, 'slug' => $notif->slug . ($notif->for == 1 ? '#support' : ($notif->for == 2 ? '#reviews' : ''))]) }}">

					                  <div class="image" style="background-image: url({{ asset_("storage/".($notif->for == 0 ? 'covers' : 'avatars')."/{$notif->image}") }})"></div>

					                  <div class="content pl-1">
					                    <p>{!! __($notif->text, ['product_name' => "<strong>{$notif->name}</strong>"]) !!}</p>
					                    <time>{{ \Carbon\Carbon::parse($notif->updated_at)->diffForHumans() }}</time>
					                  </div>

					                </a>
					                @endforeach
					              </div>

					              @else
					              
					              <div class="item mx-0">
					                <div class="ui w-100 borderless shadowless rounded-corner message p-1">
					                  {{ __('You have 0 new notifications') }}
					                </div>
					              </div>
					              
					              @endif

					              @auth
					              <a href="{{ route('home.notifications') }}" class="item mx-0 all">{{ __('View all') }}</a>
					              @endauth
					            </div>
					            
					          </div>
					        </div>
					      </div>
					      @endif


					      @if(config('payments.enable_add_to_cart'))
					      <div class="item cart dropdown toggler">
					        <div><img src="/assets/images/bag.webp" alt=""><span v-cloak>(@{{ cartItems }})</span></div>

					        <div class="menu" v-if="Object.keys(cart).length">
					          <div>
					            <div class="ui unstackable items">
					              
					              <div class="items-wrapper">
					                <div class="item mx-0" v-for="product in cart">
					                  <div class="image" :style="'background-image: url('+ product.cover +')'"></div>
					                  <div class="content pl-1">
					                    <strong :title="product.name"><a :href="product.url">@{{ product.name }}</a></strong> 
					                    <div class="subcontent mt-1">
					                      <div class="price">
					                        @{{ price(product.price, true) }}
					                      </div>
					                      <div class="remove" :disabled="couponRes.status">
					                        <i class="trash alternate outline icon mx-0" @click="removeFromCart(product.id)"></i>
					                      </div>
					                    </div>
					                  </div>
					                </div>
					              </div>
					              
					              <a href="{{ route('home.checkout') }}" class="item mx-0 checkout">{{ __('Checkout') }}</a>

					            </div>
					          </div>
					        </div>

					        <div class="menu" v-else>
					          <div class="ui unstackable items">
					            <div class="item p-1-hf">
					              <div class="ui message borderless shadowless rounded-corner w-100 left aligned p-1">
					                {{ __('Your cart is empty') }}
					              </div>
					            </div>
					          </div>
					        </div>
					      </div>
					      @endif

					      @guest
					      <a href="{{ route('login', ['redirect' => url()->current()]) }}" class="item">
					        <span class="text">{{ __('Account') }}</span>
					      </a>
					      @endguest

					      @auth
					      <div class="item ui dropdown user">
					          <img src="{{ asset_("storage/avatars/". if_null(auth()->user()->avatar, 'default.webp')) }}" class="ui avatar image mx-0">
					      
					          <div class="left menu">
					            @if(auth_is_admin())
					              <a class="item" href="{{ route('admin') }}">
					                <i class="circle blue icon"></i>
					                {{ __('Administration') }}
					              </a>

					              <a class="item" href="{{ route('profile.edit') }}">
					                  <i class="right angle icon"></i>
					                  {{ __('Profile') }}
					              </a>

					              <a class="item" href="{{ route('transactions') }}">
					                  <i class="right angle icon"></i>
					                  {{ __('Transactions') }}
					              </a>

					              <a class="item" href="{{ route('settings', ['settings_name' => 'general']) }}">
					                  <i class="right angle icon"></i>
					                  {{ __('Settings') }}
					              </a>

					              <a class="item" href="{{ route('products') }}">
					                  <i class="right angle icon"></i>
					                  {{ __('Products') }}
					              </a>

					              <a class="item" href="{{ route('pages') }}">
					                  <i class="right angle icon"></i>
					                  {{ __('Pages') }}
					              </a>

					              <a class="item" href="{{ route('posts') }}">
					                  <i class="right angle icon"></i>
					                  {{ __('Posts') }}
					              </a>

					              <a class="item" href="{{ route('categories') }}">
					                  <i class="right angle icon"></i>
					                  {{ __('Categories') }}
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
					                  <i class="right angle icon"></i>
					                  {{ __('Profile') }}
					              </a>

					              <a class="item" href="{{ route('home.favorites') }}">
					                  <i class="right angle icon"></i>
					                  {{ __('Collection') }}
					              </a>

					              <a class="item" href="{{ route('home.notifications') }}">
					                  <i class="right angle icon"></i>
					                  {{ __('Notifications') }}
					              </a>

					              <a class="item" href="{{ route('home.user_prepaid_credits') }}">
					              		<i class="right angle icon"></i>
						                {{ __('Prepaid credits') }}
						            </a>

					              <a class="item" href="{{ route('home.user_subscriptions') }}">
					                  <i class="right angle icon"></i>
					                  {{ __('Subscriptions') }}
					              </a>

					              <a class="item" href="{{ route('home.purchases') }}">
					                  <i class="right angle icon"></i>
					                  {{ __('Purchases') }}
					              </a>

					              <a class="item" href="{{ route('home.invoices') }}">
					                  <i class="right angle icon"></i>
					                  {{ __('Invoices') }}
					              </a>

					              <a class="item" href="{{ route('home.user_coupons') }}">
						                <i class="right angle icon"></i>
						                {{ __('Coupons') }}
						            </a>
					            @endif

					            <div class="ui divider my-0"></div>

					            <a class="item logout w-100 mx-0" @click="logout">
					                <i class="sign out alternate icon"></i>
					                {{ __('Sign out') }}
					            </a>

					          </div>
					      </div>
					      @endauth
					      
					      <a class="item px-1 mobile-only mr-0" @click="toggleMobileMenu">
					        <i class="bars icon mx-0"></i>
					      </a>
					    </div>

					  </div>
					</div>

					<form id="mobile-top-search" method="get" action="{{ route('home.products.q') }}" class="ui form">
					    <input type="text" name="q" value="{{ request()->query('q') }}" placeholder="{{ __('Search') }}...">
					    <i class="search link icon mr-0"></i>
					</form>

					<div id="mobile-menu" class="ui vertical menu">
					  <div class="wrapper">
					    <div class="body" v-if="menu.mobile.type === null">

					      <a href="{{ route('home') }}" class="item">
					        <i class="home icon"></i>
					        {{ __('Home') }}
					      </a>

					      <a class="item" @click="setSubMenu($event, '', true, 'categories')">
					        <i class="tags icon"></i>
					        {{ __('Categories') }}
					      </a>
					      
					      @if(config('app.subscriptions.enabled') == 1)
					      <a href="{{ route('home.subscriptions') }}" class="item">
					        <i class="dollar sign icon"></i>
					        {{ __('Pricing') }}
					      </a>
					      @endif
					      
					      @if(config('app.blog.enabled') == 1)
					      <a href="{{ route('home.blog') }}" class="item">
					        <i class="bold icon"></i>
					        {{ __('Blog') }}
					      </a>
					      @endif

					      <a href="{{ route('home.favorites') }}" class="item">
					        <i class="heart outline icon"></i>
					        {{ __('Collection') }}
					      </a>

					      <a class="item" @click="setSubMenu($event, '', true, 'pages')">
					        <i class="file alternate outline icon"></i>
					        {{ __('Pages') }}
					      </a>
					      
					      @guest
					      <a href="{{ route('login') }}" class="item">
					        <i class="user outline icon"></i>
					        {{ __('Account') }}
					      </a>
					      @endguest

					      @auth
					      @if(auth_is_admin())
					      <a href="{{ route('profile.edit') }}" class="item">
					        <i class="user outline icon"></i>
					        {{ __('Profile') }}
					      </a>
					      <a class="item" href="{{ route('admin') }}">
					        <i class="chart pie icon"></i>
					        {{ __('Dashboard') }}
					      </a>
					      @else
					      <a href="{{ route('home.profile') }}" class="item">
					        <i class="user outline icon"></i>
					        {{ __('Profile') }}
					      </a>

					      <a href="{{ route('home.purchases') }}" class="item">
					        <i class="cloud download icon"></i>
					        {{ __('Purchases') }}
					      </a>
					      @endif
					      @endauth
					      
					      <a href="{{ route('home.page', 'privacy-policy') }}" class="item">
					        <i class="circle outline icon"></i>
					        {{ __('Privacy policy') }}
					      </a>

					      <a href="{{ route('home.page', 'terms-and-conditions') }}" class="item">
					        <i class="circle outline icon"></i>
					        {{ __('Terms and conditions') }}
					      </a>

					      <a href="{{ route('home.support') }}" class="item">
					        <i class="question circle outline icon"></i>
					        {{ __('Support') }}
					      </a>

					      <a class="item" @click="setSubMenu($event, '', true, 'languages')">
					        <i class="globe icon"></i>
					        {{ __('Language') }}
					      </a>

					      @auth
					      <a class="item logout" @click="logout">
					          <i class="sign out alternate icon"></i>
					          {{ __('Sign out') }}
					      </a>
					      @endauth
					    </div>

					    <div class="sub-body" v-else>
					      <div class="item link" @click="mainMenuBack">
					        <i class="arrow alternate circle left blue icon"></i>
					        {{ __('Back') }}
					      </div>

					      <div v-if="menu.mobile.type === 'categories'">
					        <div v-if="menu.mobile.selectedCategory === null">
					          <a class="item" v-for="category in menu.mobile.submenuItems" @click="setSubMenu($event, category.id, true, 'subcategories')">
					            @{{ category.name }}
					          </a>
					        </div>
					      </div>

					      <div v-else-if="menu.mobile.type === 'subcategories'">
					        <a class="item" v-for="subcategory in menu.mobile.submenuItems"
					           :href="setProductsRoute(menu.mobile.selectedCategory.slug+'/'+subcategory.slug)">
					          @{{ subcategory.name }}
					        </a>
					      </div>

					      <div v-else-if="menu.mobile.type === 'pages'">
					        <a class="item" v-for="page in menu.mobile.submenuItems" :title="page['name']"
					           :href="setPageRoute(page['slug'])">
					          @{{ page['name'] }}
					        </a>
					      </div>

					      <div v-else-if="menu.mobile.type === 'languages'">
					        @foreach(\LaravelLocalization::getSupportedLocales() as $locale_code => $supported_locale)
					        <a class="item" @click="setLocale('{{ $locale_code }}')">
					          {{ $supported_locale['native'] ?? '' }}
					        </a>
					        @endforeach
					      </div>
					    </div>
					  </div>
					</div>

					<div id="mobile-menu-2" class="ui secondary menu">
					  <a href="/" class="item">
					    <div class="icon">
					      <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="21px" height="21px" viewBox="0 0 21 21" enable-background="new 0 0 21 21" xml:space="preserve">
					        <g id="icon">
					          <polyline points="3.5,9.4 3.5,18.5 8.5,18.5 8.5,12.5 12.5,12.5 12.5,18.5 17.5,18.5 17.5,9.4" fill="none" stroke="#FFFFFF" stroke-width="1" stroke-linecap="round" stroke-miterlimit="1" stroke-linejoin="round"/>
					          <polygon points="18.35,10 10.5,3.894 2.65,10 1.5,8.522 10.5,1.523 19.5,8.522" fill="none" stroke="#FFFFFF" stroke-width="1" stroke-linecap="round" stroke-miterlimit="1" stroke-linejoin="round"/>
					        </g>
					      </svg>
					    </div>
					    <div class="text">{{ __('Home') }}</div>
					  </a>
					  <div class="ui dropdown scrolling item">
					    <div>
					      <div class="icon">
					        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="21px" height="21px" viewBox="0 0 21 21" enable-background="new 0 0 21 21" xml:space="preserve">
					          <g id="icon">
					            <path d="M18.7,18.5H2.3c-0.442,0,-0.8,-0.358,-0.8,-0.8V3.3c0,-0.442,0.358,-0.8,0.8,-0.8h16.4c0.442,0,0.8,0.358,0.8,0.8v14.4C19.5,18.142,19.142,18.5,18.7,18.5z" fill="none" stroke="#FFFFFF" stroke-width="1" stroke-linecap="round" stroke-miterlimit="1" stroke-linejoin="round"/>
					            <path d="M1.5,6.5h18M7.5,2.5v16M13.5,2.5v16M1.5,10.5h18M1.5,14.5h18" fill="none" stroke="#FFFFFF" stroke-width="1" stroke-linecap="round" stroke-miterlimit="1" stroke-linejoin="round"/>
					          </g>
					        </svg>
					      </div>
					      <div class="text">{{ __('Categories') }}</div>
					    </div>  
					    <div class="menu">
					      @foreach(config('categories.category_parents', []) as $category)
					      <a href="{{ route('home.products.category', ['category_slug' => $category->slug]) }}" class="item capitalize">
					        {{ $category->name }}
					      </a>
					      @endforeach
					    </div>
					  </div>
					  <a href="{{ !\Auth::check() ? route('login', ['redirect' => route('home.notifications')]) : route('home.notifications') }}" class="item">
					    <div class="icon">
					      <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="21px" height="21px" viewBox="0 0 21 21" enable-background="new 0 0 21 21" xml:space="preserve">
					        <g id="icon">
					          <path d="M3.788,7.39c0.295,-0.804,0.755,-1.562,1.38,-2.224c0.614,-0.65,1.329,-1.145,2.099,-1.486M1.553,6.558c0.403,-1.096,1.029,-2.131,1.881,-3.033c0.837,-0.886,1.813,-1.562,2.862,-2.026" fill="none" stroke="#FFFFFF" stroke-width="1" stroke-linecap="round" stroke-miterlimit="1" stroke-linejoin="round" opacity="0.5"/>
					          <path d="M13.787,3.68c0.77,0.34,1.485,0.836,2.099,1.486c0.625,0.662,1.084,1.42,1.38,2.224M14.756,1.499c1.05,0.464,2.026,1.14,2.862,2.026c0.852,0.902,1.479,1.936,1.881,3.033" fill="none" stroke="#FFFFFF" stroke-width="1" stroke-linecap="round" stroke-miterlimit="1" stroke-linejoin="round" opacity="0.5"/>
					          <path d="M12.553,17.57c0,1.169,-0.971,1.93,-2.095,1.93c-1.124,0,-2.065,-0.762,-2.065,-1.93" fill="none" stroke="#FFFFFF" stroke-width="1" stroke-linecap="round" stroke-miterlimit="1" stroke-linejoin="round"/>
					          <path d="M17.697,16.069c-1.06,-1.144,-2.85,-3.623,-2.85,-5.534c0,-1.806,-1.203,-3.346,-2.891,-3.942c0.014,-0.086,0.023,-0.173,0.023,-0.264c0,-0.845,-0.663,-1.529,-1.48,-1.529s-1.48,0.685,-1.48,1.529c0,0.07,0.006,0.138,0.015,0.206c-1.78,0.548,-3.068,2.131,-3.068,3.999c0,2.676,-1.766,4.307,-2.757,5.582c-1,1.039,5.23,1.5,7.244,1.5S18.757,17.212,17.697,16.069z" fill="none" stroke="#FFFFFF" stroke-width="1" stroke-linecap="round" stroke-miterlimit="1" stroke-linejoin="round"/>
					        </g>
					      </svg>
					    </div>
					    <div class="text">{{ __('Notifications') }}</div>
					  </a>
					  @if(config('payments.enable_add_to_cart'))
					  <a href="{{ route('home.checkout') }}" class="item">
					    <div class="icon">
					      <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="21px" height="21px" viewBox="0 0 21 21" enable-background="new 0 0 21 21" xml:space="preserve">
					        <g id="icon">
					          <path d="M6.8814,13.2403C8.5,13.2403,16.896,12.5585,17.5,12.5c1.0278,-0.0995,1.5,-0.6501,1.5,-1.1231l0.5,-4.1848c0,-0.4087,-0.3061,-0.7533,-0.7119,-0.8017L4.718,4.664" fill="none" stroke="#FFFFFF" stroke-width="1" stroke-linecap="round" stroke-miterlimit="1" stroke-linejoin="round"/>
					          <path d="M9.0901,18.095c0,0.776,-0.629,1.405,-1.405,1.405s-1.405,-0.629,-1.405,-1.405s0.629,-1.405,1.405,-1.405S9.0901,17.319,9.0901,18.095zM16.5503,16.69c-0.776,0,-1.405,0.629,-1.405,1.405s0.629,1.405,1.405,1.405s1.405,-0.629,1.405,-1.405S17.3263,16.69,16.5503,16.69z" fill="none" stroke="#FFFFFF" stroke-width="1" stroke-linecap="round" stroke-miterlimit="1" stroke-linejoin="round"/>
					          <path d="M18.5,16.5H6.1995c-0.2305,0,-0.3654,-0.2483,-0.4381,-0.3729c-0.1566,-0.2684,-0.1542,-0.6537,0.006,-0.937c0.0279,-0.0493,0.9105,-1.5099,0.9105,-1.5099c0.2964,-0.4944,0.2739,-1.3885,0.0808,-1.9675c-0.0348,-0.1046,-2.4411,-8.314,-2.4825,-8.4381C4.1905,3.0177,3.9036,2.5,3.5313,2.5C3.2765,2.5,1.5,2.4874,1.5,2.4874" fill="none" stroke="#FFFFFF" stroke-width="1" stroke-linecap="round" stroke-miterlimit="1" stroke-linejoin="round"/>
					          <path d="M6.1512,14.5993" fill="none" stroke="#FFFFFF" stroke-width="1" stroke-linecap="round" stroke-miterlimit="1" stroke-linejoin="round"/>
					        </g>
					      </svg>
					      <span v-cloak>(@{{ cartItems }})</span>
					    </div>
					    <div class="text">{{ __('Cart') }}</div>
					  </a>
					  @endif
					  <a href="{{ !\Auth::check() ? route('login', ['redirect' => url()->current()]) : (auth_is_admin() ? route('admin') : route('home.profile')) }}" class="item">
					    <div class="icon">
					      @if(auth_is_admin())
					      <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="21px" height="21px" viewBox="0 0 21 21" enable-background="new 0 0 21 21" xml:space="preserve">
					        <g id="icon">
					          <path d="M6.6799,18h7.6395c0.8564,0,1.6805,-0.3252,2.3077,-0.9083c1.5863,-1.475,2.6396,-3.5153,2.8386,-5.8006c0.475,-5.4535,-4.0687,-10.1129,-9.5323,-9.7736C5.2268,1.8099,1.5,5.7197,1.5,10.5c0,2.6233,1.1226,4.9842,2.9135,6.6292C5.03,17.6955,5.8428,18,6.6799,18z" fill="none" stroke="#3D73AD" stroke-width="1" stroke-linecap="round" stroke-miterlimit="1" stroke-linejoin="round"/>
					          <circle cx="10.5" cy="14" r="1.5" fill="none" stroke="#3D73AD" stroke-width="1" stroke-linecap="round" stroke-miterlimit="1" stroke-linejoin="round"/>
					          <line x1="11.497" y1="12.6" x2="15.1" y2="7.7" fill="none" stroke="#3D73AD" stroke-width="1" stroke-linecap="round" stroke-miterlimit="1" stroke-linejoin="round"/>
					          <path d="M3.5,10.5c0,-3.866,3.134,-7,7,-7s7,3.134,7,7" fill="none" stroke="#3D73AD" stroke-width="0.9" stroke-linecap="round" stroke-miterlimit="1" stroke-linejoin="round"/>
					        </g>
					      </svg>
					      @else
					      <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="21px" height="21px" viewBox="0 0 21 21" enable-background="new 0 0 21 21" xml:space="preserve">
					        <g id="icon">
					          <circle cx="10.5" cy="10.5" r="9" fill="none" stroke="#FFFFFF" stroke-width="1" stroke-miterlimit="1"/>
					          <path d="M17.2253,16.3995c-0.4244,-1.183,-1.3275,-1.4113,-4.1682,-2.5227c-0.4969,-0.2068,-0.6372,-0.52,-0.6,-0.9446c0.9599,-1.2714,1.5383,-3.3359,1.5383,-4.7737c0,-2.2266,-0.6858,-3.641,-3.4947,-3.641S7.0624,5.9259,7.0624,8.1526c0,1.4378,0.5761,3.4895,1.536,4.7609c0.0372,0.4246,-0.1042,0.7236,-0.6011,0.9304c-2.8435,1.1126,-3.746,1.3626,-4.17,2.556" fill-rule="evenodd" fill="none" stroke="#FFFFFF" stroke-width="1" stroke-linecap="round" stroke-miterlimit="1"/>
					        </g>
					      </svg>
					      @endif
					    </div>
					    <div class="text">{{ auth_is_admin() ? __('Dashboard') : __('Account') }}</div>
					  </a>
					</div>
				</div>

				<div class="row">
					@yield('top-search')
				</div>
				
				<div class="row my-1" id="body">
					@yield('body')
				</div>
				
				<div id="blur" @click="toggleMobileMenu" v-if="!menu.mobile.hidden" v-cloak></div>

				@if(config('app.recently_viewed_items') && !route_is('home.support'))
				<div id="recently-viewed-items" class="mb-2" v-if="Object.keys(recentlyViewedItems).length > 0">
					<div class="title">
						{{ __('Recently viewed items') }}
					</div>
					<div class="items">
						<div :title="viewedItem.name" class="item" v-for="viewedItem, k in recentlyViewedItems">
							<span class="remove" @click="removeRecentViewedItem(k)"><i class="close icon mx-0"></i></span>
							<a :href="'/item/'+viewedItem.id+'/'+viewedItem.slug" class="image" :style="'background-image: url('+ viewedItem.cover +')'"></a>
						</div>
					</div>
				</div>
				@endif

				<footer id="footer">
					<div class="heading">
						<div class="header">{{ config('app.name') }}</div>
						<div class="description">{!! config('app.description') !!}</div>
					</div>
					
					<div class="footer-menu">
						<div class="ui secondary menu">
							<a href="{{ route('home') }}" class="item">{{ __('Home') }}</a>

							@if(config('app.blog.enabled') == 1)
							<a class="item" href="{{ route('home.blog') }}">{{ __('Blog') }}</a>
							@endif
							
							<a class="item" href="{{ route('home.support') }}">{{ __('Help') }}</a>

							@if(config('affiliate.enabled') == 1)
							<a href="{{ route('home.affiliate') }}" class="item">{{ __('Affiliate Program') }}</a>
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

							@foreach(config('pages', []) as $page)
							<a href="{{ route('home.page', $page['slug']) }}" class="item">{{ __($page['name']) }}</a>
							@endforeach
						</div>
					</div>

					<div class="copyright">
						<span class="item">{{ config('app.name') }} © {{ date('Y') }} {{ __('All right reserved') }}</span>
					</div>

					@auth
						<form id="logout-form" action="{{ route('logout', ['redirect' => url()->full()]) }}" method="POST" class="d-none">@csrf</form>
					@endauth

					<form action="{{ route('set_locale') }}" method="post" class="d-none" id="set-locale">
						<input type="hidden" name="redirect" value="{{ url()->full() }}">
						<input type="hidden" name="locale" v-model="locale">
					</form>
				</footer>
			</div>

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
		
		<div class="ui dimmer" id="main-dimmer">
			<div class="ui text loader">{{ __('Processing') }}</div>
		</div>

		@yield('pre_js')

		{{-- App JS --}}
	  <script type="application/javascript" src="{{ asset_('assets/front/tendra.js') }}"></script>
		
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