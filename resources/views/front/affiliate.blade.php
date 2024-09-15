<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
	<head>
		<meta charset="UTF-8">
		<meta name="language" content="{{ str_replace('_', '-', app()->getLocale()) }}">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link rel="icon" href="{{ asset_("storage/images/".config('app.favicon'))}}">
			
		{!! config('app.google_analytics') !!}
		{!! place_ad('popup_ad') !!}
		{!! place_ad('auto_ad') !!}

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
		
		{{-- jQuery --}}  
		<script type="application/javascript" src="{{ asset_('assets/jquery-3.6.0.min.js') }}"></script>

		<style>
			@font-face {
			  font-family: 'Valexa';
			  src: url('/assets/fonts/Poppins/Poppins-Regular.ttf');
			  font-weight: 400;
			  font-style: normal;
			}

			@font-face {
			  font-family: 'Valexa';
			  src: url('/assets/fonts/Poppins/Poppins-Medium.ttf');
			  font-weight: 500;
			  font-style: normal;
			}

			@font-face {
			  font-family: 'Valexa';
			  src: url('/assets/fonts/Poppins/Poppins-SemiBold.ttf');
			  font-weight: 600;
			  font-style: normal;
			}

			@font-face {
			  font-family: 'Valexa';
			  src: url('/assets/fonts/Poppins/Poppins-Bold.ttf');
			  font-weight: 700;
			  font-style: normal;
			}

			@font-face {
			  font-family: 'Valexa';
			  src: url('/assets/fonts/Poppins/Poppins-ExtraBold.ttf');
			  font-weight: 800;
			  font-style: normal;
			}

			@font-face {
			  font-family: 'Valexa';
			  src: url('/assets/fonts/Poppins/Poppins-Black.ttf');
			  font-weight: 900;
			  font-style: normal;
			}	
		</style>

    {{-- Semantic-UI --}}
    <link rel="stylesheet" href="{{ asset_('assets/semantic-ui/semantic.min.2.4.2-'.locale_direction().'.css') }}">
    <script type="application/javascript" src="{{ asset_('assets/semantic-ui/semantic.min.2.4.2.js') }}"></script>

    {{-- Spacing CSS --}}
		<link rel="stylesheet" href="{{ asset_('assets/css-spacing/spacing-'.locale_direction().'.css') }}">

		{{-- App CSS --}}
		<link rel="stylesheet" href="{{ asset_('assets/front/affiliate-'.locale_direction().'.css') }}">    
	</head>

	<body dir="{{ locale_direction() }}">
		
		<div class="ui main fluid container {{ str_ireplace('.', '_', \Route::currentRouteName()) }}">
			<div class="panel">
				<div class="top-menu">
					<div class="ui secondary menu">
						<div class="header item logo">
							<img loading="lazy" class="ui image" src="{{ asset_("storage/images/".config('app.logo')) }}" alt="{{ config('app.name') }}">
						</div>

						<div class="right menu">
							<a href="{{ route('home.support') }}" class="item">{{ __('support') }}</a>
							@auth
								@if(auth_is_admin())
								<a href="{{ route('admin') }}" class="item">{{ __('Administration') }}</a>
								@else
								<a href="{{ route('home.profile') }}" class="item">{{ __('Profile') }}</a>
								@endif
							@else
							<a href="{{ route('login', ['redirect' => route('home.affiliate')]) }}" class="item">{{ __('Acccount') }}</a>
							@endif
						</div>
					</div>
				</div>

				<div class="container">
					<div class="content">
						<div class="header">
							<div class="heading">
								{{ __('Join :name affiliate program and grow', ['name' => mb_ucfirst(config('app.name'))]) }}
							</div>
							<div class="subheading">
								{{ __('Refer new customers to :app_name and receive :commission% of their purchases', ['app_name' => config('app.name'), 'commission' => config('affiliate.commission', 0)]) }}
							</div>
							<a href="{{ \Auth::check() ? route('home.profile') : route('register') }}" class="join">{{ __('Become affiliate') }}</a>
						</div>
						<div class="thumb"><img src="/assets/images/affiliate-top-thumb.webp"></div>
					</div>
				</div>	
			</div>

			<div class="section first">
				<div class="wrapper">
					<div class="image">
						<img loading="lazy" src="{{ asset('storage/images/affiliate-1.png')  }}">
					</div>
					<div class="content ml-1">
						<div class="header">{{ __('Spread the word & start making money') }}</div>
						<div class="description">{{ __('Refer new customers to :app_name using your affiliate link and you will receive :commission% of any purchase. Our affiliate tracking cookie lasts :expire days. You will receive commission from all customers that sign up within :expire days after clicking on your affiliate links.', ['app_name' => config('app.name'), 'commission' => config('affiliate.commission', 0), 'expire' => config('affiliate.expire', 0)]) }}</div>
					</div>
				</div>
			</div>

			<div class="section second">
				<div class="wrapper">
					<div class="content mr-1">
						<div class="header">{{ __('Creating your affiliate links') }}</div>
						<div class="description">
							<p>{{ __('To be able to use our affiliate program. You will need to link to :app_name using your affiliate name from your profile page.', ['app_name' => config('app.name')]) }}</p>
							<p>{{ __("Add ?r=AFFILIATE_NAME to any :app_name link, replace AFFILIATE_NAME with your affiliate name and that's it.", ['app_name' => config('app.name')]) }}</p>
							<div class="examples">
								<div class="title">{{ __('Examples') }}</div>
								<div><span>{{ __('Homepage') }}</span> : {{ env('APP_URL') }}<span>?r=AFFILIATE_NAME</span></div>
								<div><span>{{ __('Item') }}</span> : {{ env('APP_URL') }}/item/62/amaze-ball<span>?r=AFFILIATE_NAME</span></div>
								<div><span>{{ __('Category') }}</span> : {{ env('APP_URL') }}/items/category/graphics<span>?r=AFFILIATE_NAME</span></div>
							</div>
						</div>
					</div>
					<div class="image">
						<img loading="lazy" src="{{ asset('storage/images/affiliate-2.png')  }}">
					</div>
				</div>
			</div>

			<div class="section third">
				<div class="wrapper">
					<div class="image">
						<img loading="lazy" src="{{ asset('storage/images/affiliate-3.png')  }}">
					</div>
					<div class="content ml-1">
						<div class="header">{{ __('Social Sharing') }}</div>
						<div class="description">{{ __("On every item's page there are share buttons to share an item across several social networks including Facebook, Pinterest, Twitter and more. Just click on any share button and you will receive commission on every referred sale.") }}</div>
					</div>
				</div>
			</div>

			<div class="section fourth">
				<div class="wrapper">
					<div class="content mr-1">
						<div class="header">{{ __('Cash out your earnings') }}</div>
						<div class="description">
							{!! config('affiliate.cashout_description') !!}
						</div>
					</div>
					<div class="image">
						<img loading="lazy" src="{{ asset('storage/images/affiliate-4.png')  }}">
					</div>
				</div>
			</div>

			<footer id="footer">
				<div class="heading">
					<div class="header">{{ config('app.name') }}</div>
					<div class="description">{!! config('app.description') !!}</div>
				</div>
				
				<div class="footer-menu">
					<div class="ui secondary menu stackable">
						<a href="{{ route('home') }}" class="item">{{ __('Home') }}</a>

						@if(config('app.blog.enabled'))
						<a class="item" href="{{ route('home.blog') }}">{{ __('Blog') }}</a>
						@endif
						
						<a class="item" href="{{ route('home.support') }}">{{ __('Help') }}</a>

						@if(count(config('langs') ?? []) > 1)
				    <div class="item ui top dropdown languages">
				      <div class="text capitalize">{{ __(config('laravellocalization.supportedLocales.'.session('locale', 'en').'.name')) }}</div>
				    
				      <div class="left menu rounded-corner">
				      	<div class="header">{{ __('Languages') }}</div>
				      	<div class="wrapper">
					        @foreach(\LaravelLocalization::getSupportedLocales() as $locale_code => $supported_locale)
					        <a class="item" data-value="{{ $locale_code }}">
					          {{ $supported_locale['native'] ?? '' }}
					        </a>
					        @endforeach
					      </div>
				      </div>
				    </div>
				    @endif
					</div>
				</div>

				<form action="{{ route('set_locale') }}" method="post" class="d-none" id="set-locale">
					<input type="hidden" name="redirect" value="{{ url()->full() }}">
					<input type="hidden" name="locale" v-model="locale">
				</form>

				<script type="application/javascript">
					'use strict';
					
					$(function()
					{
						$('.ui.dropdown.languages').dropdown({
							action: function(text, value) 
							{
					      $('#set-locale input[name=locale]').val(value);
					      $('#set-locale').submit();
					    }
						})
					})
				</script>
			</footer>
		</div>

	</body>
</html>