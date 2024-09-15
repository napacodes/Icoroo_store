@extends('back.master')

@section('title', __('General settings'))

@section('additional_head_tags')

<link href="{{ asset_('assets/admin/summernote-lite-0.8.12.css') }}" rel="stylesheet">
<script src="{{ asset_('assets/admin/summernote-lite-0.8.12.js') }}"></script>

@endsection


@section('content')

<form class="ui large main form" method="post" spellcheck="false" action="{{ route('settings.update', 'general') }}" enctype="multipart/form-data" id="settings-form">
	<div class="field">
		<button type="submit" class="ui circular large labeled icon button mx-0">
		  <i class="save outline icon mx-0"></i>
		  {{ __('Update') }}
		</button>
	</div>

	@if($errors->any())
      @foreach ($errors->all() as $error)
         <div class="ui negative fluid small message">
         	<i class="times icon close"></i>
         	{{ $error }}
         </div>
      @endforeach
	@endif

	@if(session('settings_message'))
	<div class="ui positive fluid message">
		<i class="times icon close"></i>
		{{ session('settings_message') }}
	</div>
	@endif

	<div class="ui fluid divider"></div>

	<div class="one column grid general" id="settings">
		<div class="column">
			<div class="nav">
				<a class="item left" data-scrollpos="0"><i class="angle left icon mr-0"></i></a>
				<div class="ui secondary unstackable menu">
					<a class="item active" data-tab="general">{{ __('General') }}</a>
					<a class="item" data-tab="blog">{{ __('Blog') }}</a>
					<a class="item" data-tab="subscriptions">{{ __('Subscriptions') }}</a>
					<a class="item" data-tab="products">{{ __('Products') }}</a>
					<a class="item" data-tab="prepaid_credits">{{ __('Prepaid credits') }}</a>
					{{-- <a class="item" data-tab="direct_download_links">{{ __('Direct download links') }}</a> --}}
					<a class="item" data-tab="users_authentication">{{ __('Users & Authentication') }}</a>
					<a class="item" data-tab="cookies">{{ __('Cookies') }}</a>
					<a class="item" data-tab="languages">{{ __('Languages') }}</a>
					<a class="item" data-tab="fonts">{{ __('Fonts') }}</a>
					<a class="item" data-tab="templates">{{ __('Templates') }}</a>
					{{-- <a class="item" data-tab="home_page">{{ __('Home page') }}</a> --}}
					<a class="item" data-tab="social">{{ __('Social') }}</a>
					<a class="item" data-tab="favicon_logo_cover">{{ __('Favicon, Logo, Cover') }}, ...</a>
					<a class="item" data-tab="notification">{{ __('Notification') }}</a>
					<a class="item" data-tab="reviews_and_comments">{{ __('Reviews and comments') }}</a>
					<a class="item" data-tab="debugging">{{ __('Debugging') }}</a>
					<a class="item" data-tab="realtime_views">{{ __('Realtime views') }}</a>
					<a class="item" data-tab="fake_purchases">{{ __('Fake purchases') }}</a>
					<a class="item" data-tab="facebook_pixel">{{ __('Facebook pixel') }}</a>
					<a class="item" data-tab="traffic_security">{{ __('Traffic security') }}</a>
					<a class="item" data-tab="other">{{ __('Other') }}</a>
				</div>
				<a class="item right" data-scrollpos="0"><i class="angle right icon mr-0"></i></a>
			</div>

			<div class="tab active" data-tab="general">
				<div class="field" id="purchase_code">
					<label>{{ __('Your Envato purchase code') }}</label>
					<input type="text" name="purchase_code" placeholder="..." value="{{ old('purchase_code', env('PURCHASE_CODE') ?? $settings->purchase_code ?? null) }}">
				</div>

				<div class="field">
					<label>{{ __('Name') }}</label>
					<input type="text" name="name" placeholder="..." value="{{ old('name', $settings->name ?? null) }}">
				</div>

				<div class="field">
					<label>{{ __('Title') }}</label>
					<input type="text" name="title" placeholder="..." value="{{ old('title', $settings->title ?? null) }}">
				</div>
			
				<div class="field">
					<label>{{ __('Description') }}</label>
					<textarea name="description" cols="30" rows="5">{{ old('description', $settings->description ?? null) }}</textarea>
				</div>

				<div class="field">
					<label>{{ __('Email') }}</label>
					<input type="email" name="email" placeholder="..." value="{{ old('email', $settings->email ?? null) }}">
				</div>

				<div class="field">
					<label>{{ __('Keywords') }}</label>
					<input type="text" name="keywords" placeholder="..." value="{{ old('keywords', $settings->keywords ?? null) }}">
				</div>

				<div class="field">
					<label>{{ __('Items per page') }}</label>
					<input type="number" name="items_per_page" value="{{ old('items_per_page', $settings->items_per_page ?? null) }}">
				</div>

				<div class="field">
					<label>{{ __('Permalink url identifer') }}</label>
					<input type="text" name="permalink_url_identifer" value="{{ old('permalink_url_identifer', $settings->permalink_url_identifer ?? null) }}">
				</div>

				<div class="field">
					<label>{{ __('Search panel headers') }}</label>
					<input type="text" name="search_header" placeholder="Header..." value="{{ old('search_header', $settings->search_header ?? null) }}">
					<input type="text" name="search_subheader" placeholder="Subheader..." value="{{ old('search_subheader', $settings->search_subheader ?? null) }}" class="mt-1">
				</div>

				<div class="field">
					<label>{{ __('Facebook APP ID') }}</label>
					<input type="text" name="fb_app_id" placeholder="Header..." value="{{ old('fb_app_id', $settings->fb_app_id ?? null) }}">
				</div>

				<div class="field" id="timezone">
					<label>{{ __('Timezone') }}</label>
					<div class="ui dropdown floating selection">
						<input type="hidden" name="timezone" value="{{ old('timezone', $settings->timezone ?? 'Europe/London') }}">
						<div class="default text">...</div>
						<div class="menu">
							@foreach(config('app.timezones', []) as $key => $val)
							<div class="item" data-value="{{ $key }}">{{ $key }} - {!! $val !!}</div>
							@endforeach
						</div>
					</div>
				</div>

				<div class="field" id="enable_data_cache">
					<label>{{ __('Enable data cache') }}</label>
					<div class="ui selection floating dropdown">
						<input type="hidden" name="enable_data_cache" value="{{ old('enable_data_cache', $settings->enable_data_cache ?? '0') }}">
						<div class="text">{{ __('No') }}</div>
						<div class="menu">
							<a class="item" data-value="1">{{ __('Yes') }}</a>
							<a class="item" data-value="0">{{ __('No') }}</a>
						</div>
					</div>
				</div>
			</div>
			

			<div class="tab" data-tab="blog">
				<div class="field" id="blog">
					<label>{{ __('Enable blog') }}</label>
					<div class="ui selection floating dropdown">
						<input type="hidden" name="blog[enabled]" value="{{ old('blog.enabled', $settings->blog->enabled ?? '1') }}">
						<div class="text">{{ __('Yes') }}</div>
						<div class="menu">
							<a class="item" data-value="1">{{ __('Yes') }}</a>
							<a class="item" data-value="0">{{ __('No') }}</a>
						</div>
					</div>
				</div>

				<div class="field">
					<label>{{ __('Title and description') }}</label>
					<input type="text" name="blog[title]" placeholder="{{ __('Blog title') }}" value="{{ old('blog.title', $settings->blog->title ?? null) }}">
					<textarea name="blog[description]" cols="30" rows="5" placeholder="{{ __('Blog description') }}" class="mt-1">{{ old('blog.description', $settings->blog->description ?? null) }}</textarea>
				</div>

				<div class="field">
					<label>{{ __('Enable Disqus comments') }}</label>
					<div class="ui selection floating dropdown">
						<input type="hidden" name="blog[disqus]" value="{{ old('blog.disqus', $settings->blog->disqus ?? '1') }}">
						<div class="text">{{ __('Yes') }}</div>
						<div class="menu">
							<a class="item" data-value="1">{{ __('Yes') }}</a>
							<a class="item" data-value="0">{{ __('No') }}</a>
						</div>
					</div>
				</div>
			</div>


			<div class="tab" data-tab="subscriptions">
				<div class="field" id="subscriptions">
					<label>{{ __('Enable subscriptions') }}</label>
					<div class="ui selection floating dropdown">
						<input type="hidden" name="subscriptions[enabled]" value="{{ old('subscriptions.enabled', $settings->subscriptions->enabled ?? '1') }}">
						<div class="text">{{ __('Yes') }}</div>
						<div class="menu">
							<a class="item" data-value="1">{{ __('Yes') }}</a>
							<a class="item" data-value="0">{{ __('No') }}</a>
						</div>
					</div>
				</div>

				<div class="field">
					<label>{{ __('Available via subscriptions only message') }}</label>
					<input type="text" name="available_via_subscriptions_only_message" placeholder="..." value="{{ old('available_via_subscriptions_only_message', $settings->available_via_subscriptions_only_message ?? null) }}">
				</div>

				<div class="field" id="subscriptions-purchases">
					<label>{{ __('Allow accumulating subscriptions') }}</label>
					<div class="ui selection floating dropdown">
						<input type="hidden" name="subscriptions[accumulative]" value="{{ old('subscriptions.accumulative', $settings->subscriptions->accumulative ?? '1') }}">
						<div class="text">{{ __('Yes') }}</div>
						<div class="menu">
							<a class="item" data-value="1">{{ __('Yes') }}</a>
							<a class="item" data-value="0">{{ __('No') }}</a>
						</div>
					</div>
					<small>{{ __('Allow users to subscribe to a plan before their first subscription expires.') }}</small>
				</div>
			</div>

			<div class="tab" data-tab="products">
				<div class="field" id="force_download">
					<label>{{ __('Force download files') }}</label>
					<div class="ui selection floating dropdown">
						<input type="hidden" name="force_download" value="{{ old('force_download', $settings->force_download ?? '0') }}">
						<div class="text">{{ __('Yes') }}</div>
						<div class="menu">
							<a class="item" data-value="1">{{ __('Yes') }}</a>
							<a class="item" data-value="0">{{ __('No') }}</a>
						</div>
					</div>
					<small>{{ __('Do not render files on the browser') }}</small>
				</div>

				<div class="field" id="allow_download_in_test_mode">
					<label>{{ __('Allow download in test mode') }}</label>
					<div class="ui selection floating dropdown">
						<input type="hidden" name="allow_download_in_test_mode" value="{{ old('allow_download_in_test_mode', $settings->allow_download_in_test_mode ?? '0') }}">
						<div class="text">{{ __('Yes') }}</div>
						<div class="menu">
							<a class="item" data-value="1">{{ __('Yes') }}</a>
							<a class="item" data-value="0">{{ __('No') }}</a>
						</div>
					</div>
				</div>

				<div class="field" id="generate_download_links_for_missing_files">
					<label>{{ __('Generate download links for missing files') }}</label>
					<div class="ui selection floating dropdown">
						<input type="hidden" name="generate_download_links_for_missing_files" value="{{ old('generate_download_links_for_missing_files', $settings->generate_download_links_for_missing_files ?? '0') }}">
						<div class="text">{{ __('Yes') }}</div>
						<div class="menu">
							<a class="item" data-value="1">{{ __('Yes') }}</a>
							<a class="item" data-value="0">{{ __('No') }}</a>
						</div>
					</div>
				</div>

				<div class="field" id="show_add_to_cart_button_on_the_product_card">
					<label>{{ __('Show add to cart button on the product card') }}</label>
					<div class="ui selection floating dropdown">
						<input type="hidden" name="show_add_to_cart_button_on_the_product_card" value="{{ old('show_add_to_cart_button_on_the_product_card', $settings->show_add_to_cart_button_on_the_product_card ?? '0') }}">
						<div class="text">{{ __('Yes') }}</div>
						<div class="menu">
							<a class="item" data-value="1">{{ __('Yes') }}</a>
							<a class="item" data-value="0">{{ __('No') }}</a>
						</div>
					</div>
				</div>

				<div class="field" id="show_badges_on_the_product_card">
					<label>{{ __('Show trending and featured badges on the product card') }}</label>
					<div class="ui selection floating dropdown">
						<input type="hidden" name="show_badges_on_the_product_card" value="{{ old('show_badges_on_the_product_card', $settings->show_badges_on_the_product_card ?? '0') }}">
						<div class="text">{{ __('Yes') }}</div>
						<div class="menu">
							<a class="item" data-value="1">{{ __('Yes') }}</a>
							<a class="item" data-value="0">{{ __('No') }}</a>
						</div>
					</div>
				</div>

				<div class="field" id="authentication_required_to_download_free_items">
					<label>{{ __('Authentication required to download free items') }}</label>
					<div class="ui selection floating dropdown">
						<input type="hidden" name="authentication_required_to_download_free_items" value="{{ old('authentication_required_to_download_free_items', $settings->authentication_required_to_download_free_items ?? '0') }}">
						<div class="text">{{ __('Yes') }}</div>
						<div class="menu">
							<a class="item" data-value="1">{{ __('Yes') }}</a>
							<a class="item" data-value="0">{{ __('No') }}</a>
						</div>
					</div>
				</div>

				<div class="field" id="products_by_country_city">
					<label>{{ __('Enable filtering products by Country / City') }}</label>
					<div class="ui selection floating dropdown">
						<input type="hidden" name="products_by_country_city" value="{{ old('products_by_country_city', $settings->products_by_country_city ?? '1') }}">
						<div class="text">{{ __('Yes') }}</div>
						<div class="menu">
							<a class="item" data-value="1">{{ __('Yes') }}</a>
							<a class="item" data-value="0">{{ __('No') }}</a>
						</div>
					</div>
				</div>
				
				<div class="field" id="products_by_country_city">
					<label>{{ __('Randomize homepage items') }}</label>
					<div class="ui selection floating dropdown">
						<input type="hidden" name="randomize_homepage_items" value="{{ old('randomize_homepage_items', $settings->randomize_homepage_items ?? '0') }}">
						<div class="text">{{ __('Yes') }}</div>
						<div class="menu">
							<a class="item" data-value="1">{{ __('Yes') }}</a>
							<a class="item" data-value="0">{{ __('No') }}</a>
						</div>
					</div>
				</div>

				<div class="field" id="show-rating">
					<label>{{ __('Show rating') }}</label>
					<div class="ui selection floating multiple dropdown">
						<input type="hidden" name="show_rating" value="{{ old('show_rating', $settings->show_rating ?? 'product_page,product_card') }}">
						<div class="text">...</div>
						<div class="menu">
							<a class="item" data-value="product_page" title="{{ __('On the single product page') }}">{{ __('Product page') }}</a>
							<a class="item" data-value="product_card" title="{{ __('On the products page') }}">{{ __('Product card') }}</a>
						</div>
					</div>
				</div>

				<div class="field" id="show-sales">
					<label>{{ __('Show sales') }}</label>
					<div class="ui selection floating multiple dropdown">
						<input type="hidden" name="show_sales" value="{{ old('show_sales', $settings->show_sales ?? 'product_page,product_card') }}">
						<div class="text">...</div>
						<div class="menu">
							<a class="item" data-value="product_page" title="{{ __('On the single product page') }}">{{ __('Product page') }}</a>
							<a class="item" data-value="product_card" title="{{ __('On the products page') }}">{{ __('Product card') }}</a>
						</div>
					</div>
				</div>

				<div class="field" id="show-streaming-player">
					<label>{{ __('Show streaming player on product page') }}</label>
					<div class="ui selection floating dropdown">
						<input type="hidden" name="show_streaming_player" value="{{ old('show_streaming_player', $settings->show_streaming_player ?? '0') }}">
						<div class="text">{{ __('Yes') }}</div>
						<div class="menu">
							<a class="item" data-value="1">{{ __('Yes') }}</a>
							<a class="item" data-value="0">{{ __('No') }}</a>
						</div>
					</div>
				</div>

				<div class="field" id="enable_upload_links">
					<label>{{ __('Enable upload links') }}</label>
					<div class="ui selection floating dropdown">
						<input type="hidden" name="enable_upload_links" value="{{ old('enable_upload_links', $settings->enable_upload_links ?? '0') }}">
						<div class="text">{{ __('No') }}</div>
						<div class="menu">
							<a class="item" data-value="1">{{ __('Yes') }}</a>
							<a class="item" data-value="0">{{ __('No') }}</a>
						</div>
					</div>
				</div>

				<div class="field" id="update_products_extension">
					<label>{{ __('Update products extension') }}</label>
					<button class="ui orange large button basic rounded-corner mr-0 mt-1-qt" type="button">{{ __("Bulk update missing product extensions") }}</button>
				</div>
			</div>


			<div class="tab" data-tab="prepaid_credits">
				<div class="field">
					<label>{{ __('Enable prepaid credits') }}</label>
					<div class="ui selection floating dropdown">
						<input type="hidden" name="prepaid_credits[enabled]" value="{{ old('prepaid_credits.enabled', $settings->prepaid_credits->enabled ?? 1) }}">
						<div class="text">...</div>
						<div class="menu">
							<a class="item" data-value="1">{{ __('Yes') }}</a>
							<a class="item" data-value="0">{{ __('No') }}</a>
						</div>
					</div>
				</div>

				<div class="field">
					<label>{{ __('Expires in') }}</label>
					<div class="ui selection floating dropdown">
						<input type="hidden" name="prepaid_credits[expires_in_days]" value="{{ old('prepaid_credits.expires_in') }}">
						<div class="text">...</div>
						<div class="menu">
							<a class="item" data-value="365">{{ __('1 year') }}</a>
							<a class="item" data-value="274">{{ __('9 months') }}</a>
							<a class="item" data-value="183">{{ __('6 months') }}</a>
							<a class="item" data-value="92">{{ __('3 months') }}</a>
							<a class="item" data-value="31">{{ __('1 month') }}</a>
							<a class="item" data-value="15">{{ __('15 days') }}</a>
						</div>
					</div>
					
					<div class="ui horizontal divider">{{ __('Or') }}</div>

					<input type="number" name="prepaid_credits[expires_in]" value="{{ old('prepaid_credits.expires_in', $settings->prepaid_credits->expires_in ?? null) }}" placeholder="{{ __('Enter number in days') }}">
				</div>
			</div>
			

			{{-- <div class="tab" data-tab="direct_download_links">
				<div class="field" id="direct-download-links-enable">
					<label>{{ __('Enable download links') }}</label>
					<div class="ui selection floating dropdown">
						<input type="hidden" name="direct_download_links[enabled]" value="{{ old('direct_download_links.enabled', $settings->direct_download_links->enabled ?? '0') }}">
						<div class="text">{{ __('Yes') }}</div>
						<div class="menu">
							<a class="item" data-value="1">{{ __('Yes') }}</a>
							<a class="item" data-value="0">{{ __('No') }}</a>
						</div>
					</div>
				</div>

				<div class="field" id="direct-download-links-filter-by-ip">
					<label>{{ __('Filter download links by user ip') }}</label>
					<div class="ui selection floating dropdown">
						<input type="hidden" name="direct_download_links[by_ip]" value="{{ old('direct_download_links.by_ip', $settings->direct_download_links->by_ip ?? '0') }}">
						<div class="text">{{ __('Yes') }}</div>
						<div class="menu">
							<a class="item" data-value="1">{{ __('Yes') }}</a>
							<a class="item" data-value="0">{{ __('No') }}</a>
						</div>
					</div>
				</div>

				<div class="field" id="direct-download-links-filter-by-ip">
					<label>{{ __('Authentication required') }}</label>
					<div class="ui selection floating dropdown">
						<input type="hidden" name="direct_download_links[authenticated]" value="{{ old('direct_download_links.authenticated', $settings->direct_download_links->authenticated ?? '0') }}">
						<div class="text">{{ __('Yes') }}</div>
						<div class="menu">
							<a class="item" data-value="1">{{ __('Yes') }}</a>
							<a class="item" data-value="0">{{ __('No') }}</a>
						</div>
					</div>
					<small>{{ __('User must be authenticated') }}</small>
				</div>

				<div class="field" id="direct-download-links-expiration">
					<label>{{ __('Expire in x hours') }}</label>
					<input type="number" name="direct_download_links[expire_in]" value="{{ old('direct_download_links.expire_in', $settings->direct_download_links->expire_in ?? null) }}" placeholder="12">
					<small>Leave empty for no expiration</small>
				</div>
			</div> --}}

			
			<div class="tab" data-tab="users_authentication">
				<div class="field">
					<label>{{ __('Registration fields') }}</label>
					<div class="ui selection multiple floating dropdown">
						<input type="hidden" name="registration_fields" value="{{ old('registration_fields', $settings->registration_fields ?? 'name,email,password,password_confirmation') }}">
						<div class="text">...</div>
						<div class="menu">
							<a class="item" data-value="name">{{ __('Username') }}</a>
							<a class="item" data-value="password">{{ __('Password') }}</a>
							<a class="item" data-value="email">{{ __('Email') }}</a>
							<a class="item" data-value="password_confirmation">{{ __('Password confirmation') }}</a>
							<a class="item" data-value="firstname">{{ __('Firstname') }}</a>
							<a class="item" data-value="lastname">{{ __('Lastname') }}</a>
							<a class="item" data-value="avatar">{{ __('Avatar') }}</a>
							<a class="item" data-value="country">{{ __('Country') }}</a>
							<a class="item" data-value="address">{{ __('Address') }}</a>
							<a class="item" data-value="zip_code">{{ __('Zip code') }}</a>
							<a class="item" data-value="phone">{{ __('Phone') }}</a>
							<a class="item" data-value="State">{{ __('State') }}</a>
							<a class="item" data-value="id_number">{{ __('ID number') }}</a>
						</div>
					</div>
				</div>

				<div class="field">
					<label>{{ __('Required registration fields') }}</label>
					<div class="ui selection multiple floating dropdown">
						<input type="hidden" name="required_registration_fields" value="{{ old('required_registration_fields', $settings->required_registration_fields ?? 'email,password,password_confirmation') }}">
						<div class="text">...</div>
						<div class="menu">
							<a class="item" data-value="name">{{ __('Username') }}</a>
							<a class="item" data-value="email">{{ __('Email') }}</a>
							<a class="item" data-value="password">{{ __('Password') }}</a>
							<a class="item" data-value="password_confirmation">{{ __('Password confirmation') }}</a>
							<a class="item" data-value="firstname">{{ __('Firstname') }}</a>
							<a class="item" data-value="lastname">{{ __('Lastname') }}</a>
							<a class="item" data-value="avatar">{{ __('Avatar') }}</a>
							<a class="item" data-value="country">{{ __('Country') }}</a>
							<a class="item" data-value="address">{{ __('Address') }}</a>
							<a class="item" data-value="zip_code">{{ __('Zip code') }}</a>
							<a class="item" data-value="phone">{{ __('Phone') }}</a>
							<a class="item" data-value="State">{{ __('State') }}</a>
							<a class="item" data-value="id_number">{{ __('ID number') }}</a>
						</div>
					</div>
				</div>

				<div class="field">
					<label>{{ __('Email verification required') }}</label>
					<div class="ui selection floating dropdown">
						<input type="hidden" name="email_verification_required" value="{{ old('email_verification_required', $settings->email_verification_required ?? '1') }}">
						<div class="text">{{ __('Yes') }}</div>
						<div class="menu">
							<a class="item" data-value="1">{{ __('Yes') }}</a>
							<a class="item" data-value="0">{{ __('No') }}</a>
						</div>
					</div>
				</div>

				<div class="ui segment fluid rounded-corner mt-2">
					<div class="content field mb-1">
						<label class="">{{ __('Two Factor Authentication') }}</label>
					</div>
					<hr>
					<div class="content">
						<div class="field" id="two_factor_authentication">
							<label>{{ __('Enable') }}</label>
							<div class="ui selection floating dropdown">
								<input type="hidden" name="two_factor_authentication" value="{{ old('two_factor_authentication', $settings->two_factor_authentication ?? '0') }}">
								<div class="text">{{ __('Yes') }}</div>
								<div class="menu">
									<a class="item" data-value="1">{{ __('Yes') }}</a>
									<a class="item" data-value="0">{{ __('No') }}</a>
								</div>
							</div>
						</div>
						<div class="field" id="two_factor_authentication_expiry">
							<label>{{ __('Expiry in minutes') }}</label>
							<input type="number" name="two_factor_authentication_expiry" placeholder="...." value="{{ old('two_factor_authentication_expiry', $settings->two_factor_authentication_expiry ?? '0') }}">
						</div>	
					</div>
				</div>
			</div>


			<div class="tab" data-tab="cookies">
				<div class="field">
					<label>{{ __('Cookie') }}</label>
					<div class="ui segment fluid rounded-corner">
						<textarea name="cookie[text]" class="summernote" rows="4" placeholder="...">{{ old('cookie.text', $settings->cookie->text ?? null) }}</textarea>
						<div class="ui right action input mt-1">
						  <input type="text" name="cookie[background][raw]" placeholder="{{ __('Container color') }}" value="{{ old('cookie.background.raw', $settings->cookie->background ?? 'linear-gradient(45deg, #ce2929, #ce2929, #ffc65d)') }}">
							<div class="ui blue icon button" onclick="this.nextElementSibling.click()">{{ __('Container color') }}</div>
						  <input type="color" class="d-none" name="cookie[background][picker]" value="{{ old('cookie.background.raw', $settings->cookie->background ?? 'linear-gradient(45deg, #ce2929, #ce2929, #ffc65d)') }}">
						</div>
						<div class="ui right action input mt-1">
						  <input type="text" name="cookie[color][raw]" placeholder="{{ __('Text color') }}" value="{{ old('cookie.color.raw', $settings->cookie->color ?? '') }}">
							<div class="ui blue icon button" onclick="this.nextElementSibling.click()">{{ __('Text color') }}</div>
						  <input type="color" class="d-none" name="cookie[color][picker]" value="{{ old('cookie.color.raw', $settings->cookie->color ?? '') }}">
						</div>
						<div class="ui right action input mt-1">
						  <input type="text" name="cookie[button_bg][raw]" placeholder="{{ __('Button background') }}" value="{{ old('cookie.color.button_bg', $settings->cookie->button_bg ?? '') }}">
							<div class="ui blue icon button" onclick="this.nextElementSibling.click()">{{ __('Button background') }}</div>
						  <input type="color" class="d-none" name="cookie[button_bg][picker]" value="{{ old('cookie.button_bg.raw', $settings->cookie->button_bg ?? '') }}">
						</div>
					</div>
				</div>
			</div>
			
			
			<div class="tab" data-tab="languages">
				<div class="field default-lang">
					<label>{{ __('Default language') }}</label>
					<div class="ui dropdown search floating selection">
						<input type="hidden" name="default_lang" value="{{ old('default_lang', $settings->default_lang ?? 'en') }}">
						<div class="default uppercase text">...</div>
						<div class="menu">
							@foreach(array_keys(config('laravellocalization.supportedLocales')) ?? [] as $lang)
							<div class="item uppercase" data-value="{{ $lang }}">{{ $lang }}</div>
							@endforeach
						</div>
					</div>	
				</div>
				
				<div class="field" id="langs">
					<label>{{ __('Languages') }}</label>
					<div class="ui dropdown multiple search floating selection">
						<input type="hidden" name="langs" value="{{ old('langs', $settings->langs ?? 'en') }}">
						<div class="default text">...</div>
						<div class="menu">
							@foreach($langs ?? [] as $lang)
							<div class="item uppercase" data-value="{{ $lang }}">{{ $lang }}</div>
							@endforeach
						</div>
					</div>
				</div>
			</div>


			<div class="tab" data-tab="fonts">
				<div class="field">
					<label>{{ __('Font - LTR') }}</label>
					<textarea name="fonts[ltr]" id="" cols="30" rows="5" placeholder="Google font url or font-face(s)">{!! old('fonts.ltr', $settings->fonts->ltr ?? null) !!}</textarea>
				</div>
				<div class="field">
					<label>{{ __('Font - RTL') }}</label>
					<textarea name="fonts[rtl]" id="" cols="30" rows="5" placeholder="Google font url or font-face(s)">{!! old('fonts.rtl', $settings->fonts->rtl ?? null) !!}</textarea>
				</div>
				<small><i class="circular exclamation small red icon"></i>{{ __("Fonts files/folders can be put in 'public/assets/fonts' and are accessible via '/assets/fonts/(FONT_FOLDER)/FILE_NAME' .") }}</small>
			</div>


			<div class="tab" data-tab="templates">
				<div class="field" id="template">
					<label>{{ __('Templates') }}</label>
					<div class="ui dropdown floating selection">
						<input type="hidden" name="template" value="{{ old('template', $settings->template ?? 'valexa') }}">
						<div class="text capitalize">...</div>
						<div class="menu">
							@foreach($templates as $template)
							<div class="item capitalize" data-value="{{ $template }}">{{ $template }}</div>
							@endforeach
						</div>
					</div>
				</div>

				<div class="field {{ ($settings->template ?? null) != "axies" ? "d-none" : "" }}" id="template-mode">
					<label>{{ __('Fullwide mode') }}</label>
					<div class="ui dropdown floating selection">
						<input type="hidden" name="fullwide" value="{{ old('fullwide', $settings->fullwide ?? '0') }}">
						<div class="text capitalize">...</div>
						<div class="menu">
							<div class="item capitalize" data-value="0">{{ __('No') }}</div>
							<div class="item capitalize" data-value="1">{{ __('Yes') }}</div>
						</div>
					</div>
				</div>

				<div class="field {{ ($settings->template ?? null) != "axies" ? "d-none" : "" }}" id="categories-on-homepage">
					<label>{{ __('Show top categories on homepage') }}</label>
					<div class="ui dropdown floating selection">
						<input type="hidden" name="categories_on_homepage" value="{{ old('categories_on_homepage', $settings->categories_on_homepage ?? '0') }}">
						<div class="text capitalize">...</div>
						<div class="menu">
							<div class="item capitalize" data-value="0">{{ __('No') }}</div>
							<div class="item capitalize" data-value="1">{{ __('Yes') }}</div>
						</div>
					</div>
				</div>

				<div class="field {{ ($settings->template ?? null) != "tendra" ? "d-none" : "" }}" id="product-card-cover-mask">
					<label>{{ __('Product card cover mask') }}</label>
					<div class="ui dropdown floating selection">
						<input type="hidden" name="product_card_cover_mask" value="{{ old('product_card_cover_mask', $settings->product_card_cover_mask ?? null) }}">
						<div class="text">...</div>
						<div class="menu">
							<div class="item" data-value="">&nbsp;</div>
							@foreach($product_card_cover_masks ?? [] as $mask)
							<div class="item" data-value="{{ $mask }}">{{ $mask }}</div>
							@endforeach
						</div>
					</div>
				</div>

				<div class="field">
					<label>{{ __('Custom Javascript & CSS code to execute on the front-end') }}</label>
					<textarea name="js_css_code[frontend]" rows="5" placeholder="...">{{ old('js_css_code.frontend', $settings->js_css_code->frontend ?? null) }}</textarea>
				</div>

				<div class="field">
					<label>{{ __('Custom Javascript & CSS code to execute on the back-end') }}</label>
					<textarea name="js_css_code[backend]" rows="5" placeholder="...">{{ old('js_css_code.backend', $settings->js_css_code->backend ?? null) }}</textarea>
				</div>
			</div>

			{{-- <div class="tab" data-tab="home_page">
				<div class="field homepage-items" id="homepage-items">
					<label>{{ __('Home page items') }}</label>
					<div class="table-wrapper">
						<table class="ui fluid unstackable celled striped small table default mt-0 {{ config('app.template') === 'default' ? '' : 'd-none' }}">
							<tbody>
								<tr>
									<th>{{ __('Featured items') }}</th>
									<td>
										<input type="number" placeholder="{{ __('Limit of items to show') }}" name="homepage_items[default][featured][limit]" value="{{ old('homepage_items.default.featured.limit', $settings->homepage_items->default->featured->limit ?? null) }}">
									</td>
									<td>
										<input type="number" placeholder="{{ __('Items per line') }}" name="homepage_items[default][featured][items_per_line]" value="{{ old('homepage_items.default.featured.items_per_line', $settings->homepage_items->default->featured->items_per_line ?? null) }}">
									</td>
								</tr>
								<tr>
									<th>{{ __('Trending items') }}</th>
									<td>
										<input type="number" placeholder="{{ __('Limit of items to show') }}" name="homepage_items[default][trending][limit]" value="{{ old('homepage_items.default.trending.limit', $settings->homepage_items->default->trending->limit ?? null) }}">
									</td>
									<td>
										<input type="number" placeholder="{{ __('Items per line') }}" name="homepage_items[default][trending][items_per_line]" value="{{ old('homepage_items.default.trending.items_per_line', $settings->homepage_items->default->trending->items_per_line ?? null) }}">
									</td>
								</tr>
								<tr>
									<th>{{ __('Newest items') }}</th>
									<td>
										<input type="number" placeholder="{{ __('Limit of items to show') }}" name="homepage_items[default][newest][limit]" value="{{ old('homepage_items.default.newest.limit', $settings->homepage_items->default->newest->limit ?? null) }}">
									</td>
									<td>
										<input type="number" placeholder="{{ __('Items per line') }}" name="homepage_items[default][newest][items_per_line]" value="{{ old('homepage_items.default.newest.items_per_line', $settings->homepage_items->default->newest->items_per_line ?? null) }}">
									</td>
								</tr>
								<tr>
									<th>{{ __('Free items') }}</th>
									<td>
										<input type="number" placeholder="{{ __('Limit of items to show') }}" name="homepage_items[default][free][limit]" value="{{ old('homepage_items.default.free.limit', $settings->homepage_items->default->free->limit ?? null) }}">
									</td>
									<td>
										<input type="number" placeholder="{{ __('Items per line') }}" name="homepage_items[default][free][items_per_line]" value="{{ old('homepage_items.default.free.items_per_line', $settings->homepage_items->default->free->items_per_line ?? null) }}">
									</td>
								</tr>
								<tr>
									<th>{{ __('Posts') }}</th>
									<td>
										<input type="number" placeholder="{{ __('Limit of items to show') }}" name="homepage_items[default][posts][limit]" value="{{ old('homepage_items.default.posts.limit', $settings->homepage_items->default->posts->limit ?? null) }}">
									</td>
									<td>
										<input type="number" placeholder="{{ __('Items per line') }}" name="homepage_items[default][posts][items_per_line]" value="{{ old('homepage_items.default.posts.items_per_line', $settings->homepage_items->default->posts->items_per_line ?? null) }}">
									</td>
								</tr>
							</tbody>
						</table>

						<table class="ui fluid unstackable celled striped small table valexa mt-0 {{ config('app.template') === 'valexa' ? '' : 'd-none' }}">
							<tbody>
								<tr>
									<th>{{ __('Featured items') }}</th>
									<td>
										<input type="number" placeholder="{{ __('Limit of items to show') }}" name="homepage_items[valexa][featured][limit]" value="{{ old('homepage_items.valexa.featured.limit', $settings->homepage_items->valexa->featured->limit ?? null) }}">
									</td>
									<td>
										<input type="number" placeholder="{{ __('Items per line') }}" name="homepage_items[valexa][featured][items_per_line]" value="{{ old('homepage_items.valexa.featured.items_per_line', $settings->homepage_items->valexa->featured->items_per_line ?? null) }}">
									</td>
								</tr>
								<tr>
									<th>{{ __('Trending items') }}</th>
									<td>
										<input type="number" placeholder="{{ __('Limit of items to show') }}" name="homepage_items[valexa][trending][limit]" value="{{ old('homepage_items.valexa.trending.limit', $settings->homepage_items->valexa->trending->limit ?? null) }}">
									</td>
									<td>
										<input type="number" placeholder="{{ __('Items per line') }}" name="homepage_items[valexa][trending][items_per_line]" value="{{ old('homepage_items.valexa.trending.items_per_line', $settings->homepage_items->valexa->trending->items_per_line ?? null) }}">
									</td>
								</tr>
								<tr>
									<th>{{ __('Newest items') }}</th>
									<td>
										<input type="number" placeholder="{{ __('Limit of items to show') }}" name="homepage_items[valexa][newest][limit]" value="{{ old('homepage_items.valexa.newest.limit', $settings->homepage_items->valexa->newest->limit ?? null) }}">
									</td>
									<td>
										<input type="number" placeholder="{{ __('Items per line') }}" name="homepage_items[valexa][newest][items_per_line]" value="{{ old('homepage_items.valexa.newest.items_per_line', $settings->homepage_items->valexa->newest->items_per_line ?? null) }}">
									</td>
								</tr>
								<tr>
									<th>{{ __('Free items') }}</th>
									<td>
										<input type="number" placeholder="{{ __('Limit of items to show') }}" name="homepage_items[valexa][free][limit]" value="{{ old('homepage_items.valexa.free.limit', $settings->homepage_items->valexa->free->limit ?? null) }}">
									</td>
									<td>
										<input type="number" placeholder="{{ __('Items per line') }}" name="homepage_items[valexa][free][items_per_line]" value="{{ old('homepage_items.valexa.free.items_per_line', $settings->homepage_items->valexa->free->items_per_line ?? null) }}">
									</td>
								</tr>
								<tr>
									<th>{{ __('Posts') }}</th>
									<td>
										<input type="number" placeholder="{{ __('Limit of items to show') }}" name="homepage_items[valexa][posts][limit]" value="{{ old('homepage_items.valexa.posts.limit', $settings->homepage_items->valexa->posts->limit ?? null) }}">
									</td>
									<td>
										<input type="number" placeholder="{{ __('Items per line') }}" name="homepage_items[valexa][posts][items_per_line]" value="{{ old('homepage_items.valexa.posts.items_per_line', $settings->homepage_items->valexa->posts->items_per_line ?? null) }}">
									</td>
								</tr>
							</tbody>
						</table>

						<table class="ui fluid unstackable celled striped small table tendra mt-0 {{ config('app.template') === 'tendra' ? '' : 'd-none' }}">
							<tbody>
								<tr>
									<th>{{ __('Featured items') }}</th>
									<td>
										<input type="number" placeholder="{{ __('Limit of items to show') }}" name="homepage_items[tendra][featured][limit]" value="{{ old('homepage_items.tendra.featured.limit', $settings->homepage_items->tendra->featured->limit ?? null) }}">
									</td>
									<td>
										<input type="number" placeholder="{{ __('Items per line') }}" name="homepage_items[tendra][featured][items_per_line]" value="{{ old('homepage_items.tendra.featured.items_per_line', $settings->homepage_items->tendra->featured->items_per_line ?? null) }}">
									</td>
								</tr>
								<tr>
									<th>{{ __('Newest items') }}</th>
									<td>
										<input type="number" placeholder="{{ __('Limit of items to show') }}" name="homepage_items[tendra][newest][limit]" value="{{ old('homepage_items.tendra.newest.limit', $settings->homepage_items->tendra->newest->limit ?? null) }}">
									</td>
									<td>
										<input type="number" placeholder="{{ __('Items per line') }}" name="homepage_items[tendra][newest][items_per_line]" value="{{ old('homepage_items.tendra.newest.items_per_line', $settings->homepage_items->tendra->newest->items_per_line ?? null) }}">
									</td>
								</tr>
								<tr>
									<th>{{ __('Free items') }}</th>
									<td>
										<input type="number" placeholder="{{ __('Limit of items to show') }}" name="homepage_items[tendra][free][limit]" value="{{ old('homepage_items.tendra.free.limit', $settings->homepage_items->tendra->free->limit ?? null) }}">
									</td>
									<td>
										<input type="number" placeholder="{{ __('Items per line') }}" name="homepage_items[tendra][free][items_per_line]" value="{{ old('homepage_items.tendra.free.items_per_line', $settings->homepage_items->tendra->free->items_per_line ?? null) }}">
									</td>
								</tr>
								<tr>
									<th>{{ __('Posts') }}</th>
									<td>
										<input type="number" placeholder="{{ __('Limit of items to show') }}" name="homepage_items[tendra][posts][limit]" value="{{ old('homepage_items.tendra.posts.limit', $settings->homepage_items->tendra->posts->limit ?? null) }}">
									</td>
									<td>
										<input type="number" placeholder="{{ __('Items per line') }}" name="homepage_items[tendra][posts][items_per_line]" value="{{ old('homepage_items.tendra.posts.items_per_line', $settings->homepage_items->tendra->posts->items_per_line ?? null) }}">
									</td>
								</tr>
								<tr>
									<th>{{ __('Pricing plans') }}</th>
									<td>
										<input type="number" placeholder="{{ __('Limit of items to show') }}" name="homepage_items[tendra][pricing_plans][limit]" value="{{ old('homepage_items.tendra.pricing_plans.limit', $settings->homepage_items->tendra->pricing_plans->limit ?? null) }}">
									</td>
									<td>
										<input type="number" placeholder="{{ __('Items per line') }}" name="homepage_items[tendra][pricing_plans][items_per_line]" value="{{ old('homepage_items.tendra.pricing_plans.items_per_line', $settings->homepage_items->tendra->pricing_plans->items_per_line ?? null) }}">
									</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div> --}}


			<div class="tab" data-tab="social">
				<div class="field">
					<div class="table wrapper mt-0">
						<table class="ui celled unstackable table">
							<thead>
								<tr>
									<th colspan="2">{{ __('Social links') }}</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td class="one column wide">Tiktok</td>
									<td><input type="text" name="tiktok" value="{{ old('tiktok', $settings->tiktok ?? null ) }}"></td>
								</tr>
								<tr>
									<td class="one column wide">Facebook</td>
									<td><input type="text" name="facebook" value="{{ old('facebook', $settings->facebook ?? null ) }}"></td>
								</tr>
								<tr>
									<td class="one column wide">Twitter</td>
									<td><input type="text" name="twitter" value="{{ old('twitter', $settings->twitter ?? null ) }}"></td>
								</tr>
								<tr>
									<td class="one column wide">Pinterest</td>
									<td><input type="text" name="pinterest" value="{{ old('pinterest', $settings->pinterest ?? null ) }}"></td>
								</tr>
								<tr>
									<td class="one column wide">Youtube</td>
									<td><input type="text" name="youtube" value="{{ old('youtube', $settings->youtube ?? null ) }}"></td>
								</tr>
								<tr>
									<td class="one column wide">Tumblr</td>
									<td><input type="text" name="tumblr" value="{{ old('tumblr', $settings->tumblr ?? null ) }}"></td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>


			<div class="tab" data-tab="favicon_logo_cover">
				<div class="field">
					<div class="table wrapper files">
						<table class="ui celled unstackable table">
							<thead>
								<tr>
									<th>{{ __('Favicon') }}</th>
									<th>{{ __('Logo') }}</th>
									<th>{{ __('Website Cover') }}</th>
									<th>{{ __('Top Cover') }}</th>
									<th>{{ __('Blog Cover') }}</th>
									<th>{{ __('Watermark') }}</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td>
										<button class="ui basic circular large fluid button" type="button" onclick="this.nextElementSibling.click()">
											{{ config('app.favicon') ?? __('Browse') }}
										</button>
										<input type="file" class="d-none" name="favicon" accept="image/*">
									</td>

									<td>
										<button class="ui basic circular large fluid button" type="button" onclick="this.nextElementSibling.click()">
											{{ config('app.logo') ?? __('Browse') }}
										</button>
										<input type="file" class="d-none" name="logo" accept="image/*">
									</td>

									<td>
										<button class="ui basic circular large fluid button" type="button" onclick="this.nextElementSibling.click()">
											{{ config('app.cover') ?? __('Browse') }}
										</button>
										<input type="file" class="d-none" name="cover" accept="image/*">
									</td>

									<td>
										@foreach($templates as $template)
										<div class="d-flex {{ !$loop->last ? 'mb-1' : '' }}">
											<button class="ui basic circular large button" type="button" onclick="this.nextElementSibling.click()">
												{{ config("app.top_cover.{$template}") ?? __('Browse') }} <sup>{{ $template }}</sup>
											</button>
											<input type="file" class="d-none" name="top_cover[{{ $template }}]" accept="image/*">

											@if($settings->{$template.'_top_cover'} ?? null)
											<button class="ui red inverted circular large icon button mr-0" type="button" onclick="this.nextElementSibling.remove(); this.remove()">
												<i class="close icon mx-0"></i>
											</button>
											<input type="hidden" name="top_cover[{{ $template }}]" value="{{ $settings->{$template.'_top_cover'} ?? null }}">
											@endif
										</div>
										@endforeach										
									</td>

									<td>
										<button class="ui basic circular large fluid button" type="button" onclick="this.nextElementSibling.click()">
											{{ config('app.blog_cover') ?? __('Browse') }}
										</button>
										<input type="file" class="d-none" name="blog_cover" accept="image/*">
									</td>

									<td>
										<button class="ui basic circular large fluid button" type="button" onclick="this.nextElementSibling.click()">
											{{ config('app.watermark') ?? __('Browse') }}
										</button>
										<input type="file" class="d-none" name="watermark" accept="image/*">
										@if($settings->watermark)
										<button class="ui circular large red fluid button mt-1" type="button" onclick="this.nextElementSibling.remove(); this.remove()">{{ __('Remove') }}</button>
										<input type="hidden" name="watermark" value="{{ $settings->watermark }}">
										@endif
									</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>


			<div class="tab" data-tab="notification">
				<div class="field">
					<label>{{ __('Receive email notifications on :what', ['what' => __('Comments')]) }}</label>
					<div class="ui selection floating dropdown">
						<input type="hidden" name="admin_notifications[comments]" value="{{ old('admin_notifications.comments', $settings->admin_notifications->comments ?? '1') }}">
						<div class="text">{{ __('Yes') }}</div>
						<div class="menu">
							<a class="item" data-value="1">{{ __('Yes') }}</a>
							<a class="item" data-value="0">{{ __('No') }}</a>
						</div>
					</div>
				</div>

				<div class="field">
					<label>{{ __('Receive email notifications on :what', ['what' => __('Reviews')]) }}</label>
					<div class="ui selection floating dropdown">
						<input type="hidden" name="admin_notifications[reviews]" value="{{ old('admin_notifications.reviews', $settings->admin_notifications->reviews ?? '1') }}">
						<div class="text">{{ __('Yes') }}</div>
						<div class="menu">
							<a class="item" data-value="1">{{ __('Yes') }}</a>
							<a class="item" data-value="0">{{ __('No') }}</a>
						</div>
					</div>
				</div>

				<div class="field">
					<label>{{ __('Receive email notifications on :what', ['what' => __('Sales')]) }}</label>
					<div class="ui selection floating dropdown">
						<input type="hidden" name="admin_notifications[sales]" value="{{ old('admin_notifications.sales', $settings->admin_notifications->sales ?? '1') }}">
						<div class="text">{{ __('Yes') }}</div>
						<div class="menu">
							<a class="item" data-value="1">{{ __('Yes') }}</a>
							<a class="item" data-value="0">{{ __('No') }}</a>
						</div>
					</div>
				</div>

				<div class="field">
					<label>{{ __('Enable email verification') }}</label>
					<div class="ui dropdown floating selection">
						<input type="hidden" name="email_verification" value="{{ old('email_verification', $settings->email_verification ?? '0') }}">
						<div class="default text">...</div>
						<div class="menu">
							<div class="item" data-value="1">{{ __('Yes') }}</div>
							<div class="item" data-value="0">{{ __('No') }}</div>
						</div>
					</div>
				</div>
			</div>


			<div class="tab" data-tab="reviews_and_comments">
				<div class="field" id="enable_comments">
					<label>{{ __('Enable comments') }}</label>
					<div class="ui dropdown floating selection">
						<input type="hidden" name="enable_comments" value="{{ old('enable_comments', $settings->enable_comments ?? '1') }}">
						<div class="default text">...</div>
						<div class="menu">
							<div class="item" data-value="1">{{ __('Yes') }}</div>
							<div class="item" data-value="0">{{ __('No') }}</div>
						</div>
					</div>
				</div>

				<div class="field" id="enable_reviews">
					<label>{{ __('Enable reviews') }}</label>
					<div class="ui dropdown floating selection">
						<input type="hidden" name="enable_reviews" value="{{ old('enable_reviews', $settings->enable_reviews ?? '1') }}">
						<div class="default text">...</div>
						<div class="menu">
							<div class="item" data-value="1">{{ __('Yes') }}</div>
							<div class="item" data-value="0">{{ __('No') }}</div>
						</div>
					</div>
				</div>

				<div class="field" id="auto_approve[support]">
					<label>{{ __('Auto approve comments') }}</label>
					<div class="ui dropdown floating selection">
						<input type="hidden" name="auto_approve[support]" value="{{ old('auto_approve.support', $settings->auto_approve->support ?? '0') }}">
						<div class="default text">...</div>
						<div class="menu">
							<div class="item" data-value="1">{{ __('Yes') }}</div>
							<div class="item" data-value="0">{{ __('No') }}</div>
						</div>
					</div>
				</div>

				<div class="field" id="auto_approve[reviews]">
					<label>{{ __('Auto approve reviews') }}</label>
					<div class="ui dropdown floating selection">
						<input type="hidden" name="auto_approve[reviews]" value="{{ old('auto_approve.reviews', $settings->auto_approve->reviews ?? '0') }}">
						<div class="default text">...</div>
						<div class="menu">
							<div class="item" data-value="1">{{ __('Yes') }}</div>
							<div class="item" data-value="0">{{ __('No') }}</div>
						</div>
					</div>
				</div>

				<div class="field" id="can_delete_own_comments">
					<label>{{ __('Enable deleting own comments') }}</label>
					<div class="ui selection floating dropdown">
						<input type="hidden" name="can_delete_own_comments" value="{{ old('can_delete_own_comments', $settings->can_delete_own_comments ?? '1') }}">
						<div class="text">{{ __('Yes') }}</div>
						<div class="menu">
							<a class="item" data-value="1">{{ __('Yes') }}</a>
							<a class="item" data-value="0">{{ __('No') }}</a>
						</div>
					</div>
				</div>

				<div class="field" id="can_edit_own_reviews">
					<label>{{ __('Enable editing own reviews') }}</label>
					<div class="ui selection floating dropdown">
						<input type="hidden" name="can_edit_own_reviews" value="{{ old('can_edit_own_reviews', $settings->can_edit_own_reviews ?? '1') }}">
						<div class="text">{{ __('Yes') }}</div>
						<div class="menu">
							<a class="item" data-value="1">{{ __('Yes') }}</a>
							<a class="item" data-value="0">{{ __('No') }}</a>
						</div>
					</div>
				</div>

				<div class="field" id="enable_reactions_on_comments">
					<label>{{ __('Enable reactions on comments') }}</label>
					<div class="ui dropdown floating selection">
						<input type="hidden" name="enable_reactions_on_comments" value="{{ old('enable_reactions_on_comments', $settings->enable_reactions_on_comments ?? '1') }}">
						<div class="default text">...</div>
						<div class="menu">
							<div class="item" data-value="1">{{ __('Yes') }}</div>
							<div class="item" data-value="0">{{ __('No') }}</div>
						</div>
					</div>
				</div>

				<div class="field" id="enable_subcomments">
					<label>{{ __('Enable subcomments') }}</label>
					<div class="ui dropdown floating selection">
						<input type="hidden" name="enable_subcomments" value="{{ old('enable_subcomments', $settings->enable_subcomments ?? '1') }}">
						<div class="default text">...</div>
						<div class="menu">
							<div class="item" data-value="1">{{ __('Yes') }}</div>
							<div class="item" data-value="0">{{ __('No') }}</div>
						</div>
					</div>
				</div>
			</div>


			<div class="tab" data-tab="debugging">
				<div class="field" id="env">
					<label>{{ __('Environment') }}</label>
					<div class="ui dropdown floating selection">
						<input type="hidden" name="env" value="{{ old('env', $settings->env ?? 'production') }}">
						<div class="default text">...</div>
						<div class="menu">
							<div class="item" data-value="production">{{ __('Production') }}</div>
							<div class="item" data-value="local" title="{{ __('Development') }}">{{ __('Local') }}</div>
						</div>
					</div>
				</div>

				<div class="field" id="debug">
					<label>{{ __('Mode Debug') }}</label>
					<div class="ui dropdown floating selection">
						<input type="hidden" name="debug" value="{{ old('debug', $settings->debug ?? '1') }}">
						<div class="default text">...</div>
						<div class="menu">
							<div class="item" data-value="1">{{ __('On') }}</div>
							<div class="item" data-value="0">{{ __('Off') }}</div>
						</div>
					</div>
				</div>
			</div>

			<div class="tab" data-tab="realtime_views">
				<div class="two stackable fields">
					<div class="field">
						<label>{{ __('Enable realtime website views') }}</label>
						<div class="ui dropdown floating selection">
							<input type="hidden" name="realtime_views[website][enabled]" value="{{ old('realtime_views.website.enabled', $settings->realtime_views->website->enabled ?? '1') }}">
							<div class="default text">...</div>
							<div class="menu">
								<div class="item" data-value="1">{{ __('Yes') }}</div>
								<div class="item" data-value="0">{{ __('No') }}</div>
							</div>
						</div>
					</div>
					<div class="field">
						<label>{{ __('Enable fake mode for realtime website views') }}</label>
						<div class="ui dropdown floating selection">
							<input type="hidden" name="realtime_views[website][fake]" value="{{ old('realtime_views.website.fake', $settings->realtime_views->website->fake ?? '0') }}">
							<div class="default text">...</div>
							<div class="menu">
								<div class="item" data-value="1">{{ __('Yes') }}</div>
								<div class="item" data-value="0">{{ __('No') }}</div>
							</div>
						</div>
					</div>
				</div>

				<div class="two stackable fields">
					<div class="field">
						<label>{{ __('Enable realtime product views') }}</label>
						<div class="ui dropdown floating selection">
							<input type="hidden" name="realtime_views[product][enabled]" value="{{ old('realtime_views.product.enabled', $settings->realtime_views->product->enabled ?? '1') }}">
							<div class="default text">...</div>
							<div class="menu">
								<div class="item" data-value="1">{{ __('Yes') }}</div>
								<div class="item" data-value="0">{{ __('No') }}</div>
							</div>
						</div>
					</div>
					<div class="field">
						<label>{{ __('Enable fake mode for realtime product views') }}</label>
						<div class="ui dropdown floating selection">
							<input type="hidden" name="realtime_views[product][fake]" value="{{ old('realtime_views.product.fake', $settings->realtime_views->product->fake ?? '0') }}">
							<div class="default text">...</div>
							<div class="menu">
								<div class="item" data-value="1">{{ __('Yes') }}</div>
								<div class="item" data-value="0">{{ __('No') }}</div>
							</div>
						</div>
					</div>
				</div>

				<div class="two stackable fields">
					<div class="field">
						<label>{{ __('Fake product views range') }}</label>
						<input type="text" place="min,max" name="realtime_views[product][range]" value="{{ old('realtime_views.product.range', $settings->realtime_views->product->range ?? '15,30') }}">
					</div>

					<div class="field">
						<label>{{ __('Fake website views range') }}</label>
						<input type="text" place="min,max" name="realtime_views[website][range]" value="{{ old('realtime_views.website.range', $settings->realtime_views->website->range ?? '15,30') }}">
					</div>
				</div>

				<div class="field">
					<label>{{ __('Refresh realtime views each X seconds') }}</label>
					<input type="number" name="realtime_views[refresh]" value="{{ old('realtime_views.refresh', $settings->realtime_views->refresh ?? 5) }}">
				</div>
			</div>

			<div class="tab" data-tab="fake_purchases">
				<div class="field">
					<label>{{ __('Enable fake purchases popup') }}</label>
					<div class="ui dropdown floating selection">
						<input type="hidden" name="fake_purchases[enabled]" value="{{ old('fake_purchases.enabled', $settings->fake_purchases->enabled ?? '1') }}">
						<div class="default text">...</div>
						<div class="menu">
							<div class="item" data-value="0">{{ __('No') }}</div>
							<div class="item" data-value="1">{{ __('Yes') }}</div>
						</div>
					</div>
				</div>

				<div class="field">
					<label>{{ __('Show on the following pages') }}</label>
					<div class="ui dropdown floating multiple search selection">
						<input type="hidden" name="fake_purchases[pages]" value="{{ old('fake_purchases.pages', $settings->fake_purchases->pages ?? 'product,home,products') }}">
						<div class="default text">...</div>
						<div class="menu">
							<div class="item" data-value="home">{{ __('Homepage') }}</div>
							<div class="item" data-value="products">{{ __('Products page') }}</div>
							<div class="item" data-value="product">{{ __('Single product page') }}</div>
							<div class="item" data-value="checkout">{{ __('Checkout page') }}</div>
						</div>
					</div>
				</div>

				<div class="field">
					<label>{{ __('Show popup randomly each X seconds') }} <sup>{{ __('Interval') }}</sup></label>
					<input type="text" name="fake_purchases[interval]" placeholder="{{ __('min,max') }}" value="{{ old('fake_purchases.interval', $settings->fake_purchases->interval ?? '300,900') }}">
				</div>

				<div class="ui fluid blue rounded-corner segment">
					<div class="ui header">{{ __('Generate profiles') }}</div>

					<div class="two stackable fields">
						<div class="field">
							<label>{{ __('Country') }}</label>
							<div class="ui dropdown floating selection search">
								<input type="hidden" name="fake_profiles[country]" value="{{ old('fake_profiles.country', 'random') }}">
								<div class="default text">...</div>
								<div class="menu">
									@foreach(config('app.fake_profile_locations', []) as $key => $val)
									<div class="item" data-value="{{ $key }}">{{ __($val) }}</div>
									@endforeach
								</div>
							</div>
						</div>

						<div class="field">
							<label>{{ __('Gender') }}</label>
							<div class="ui dropdown floating selection search">
								<input type="hidden" name="fake_profiles[gender]" value="{{ old('fake_profiles.gender', '-') }}">
								<div class="default text">...</div>
								<div class="menu">
									<div class="item" data-value="-">{{ __("Random") }}</div>
									<div class="item" data-value="male">{{ __("Male") }}</div>
									<div class="item" data-value="female">{{ __("Female") }}</div>
								</div>
							</div>
						</div>
					</div>

					<div class="field">
						<label>{{ __('How many profiles') }}</label>
						<input type="number" name="fake_profiles[count]" value="{{ old('fake_profiles.by_country', 10) }}">
					</div>

					<div class="field d-flex">
						<button class="ui yellow large rounded button" id="generate-fake-profiles" type="button">{{ __('Generate') }}</button>
						<button class="ui basic large rounded button ml-auto mr-0" id="list-fake-profiles" type="button">{{ __('List profiles') }}</button>
					</div>
				</div>

				<div class="ui modal" id="profiles-list">
					<div class="content main"></div>
				</div>
			</div>

			<div class="tab" data-tab="other">
				<div class="field" id="invoice">
					<label>{{ __('Invoice template') }}</label>
					<div class="ui dropdown floating selection">
						<input type="hidden" name="invoice[template]" value="{{ old('invoice.template', $settings->invoice->template ?? '1') }}">
						<div class="default text">...</div>
						<div class="menu">
							<div class="item" data-value="1">{{ __('Template 1') }}</div>
							<div class="item" data-value="2">{{ __('Template 2') }}</div>
						</div>
					</div>
				</div>

				<div class="field" id="color_cursor">
					<label>{{ __('Color cursor') }}</label>
					<div class="ui dropdown floating selection">
						<input type="hidden" name="color_cursor" value="{{ old('invoice.color_cursor', $settings->color_cursor ?? '0') }}">
						<div class="default text">...</div>
						<div class="menu">
							<div class="item" data-value="1">{{ __('Yes') }}</div>
							<div class="item" data-value="0">{{ __('No') }}</div>
						</div>
					</div>
				</div>

				<div class="field" id="invoice-tos">
					<label>{{ __('Invoice terms and conditions') }}</label>
					<textarea name="invoice[tos]" rows="5">{{ old('invoice.tos', $settings->invoice->tos ?? null) }}</textarea>
				</div>

				<div class="field" id="editor">
					<label>{{ __('HTML Editor') }}</label>
					<div class="ui dropdown floating selection">
						<input type="hidden" name="html_editor" value="{{ old('html_editor', $settings->html_editor ?? 'summernote') }}">
						<div class="default text">...</div>
						<div class="menu">
							<div class="item" data-value="summernote">{{ __('Summernote') }}</div>
							<div class="item" data-value="tinymce">{{ __('TinyMCE') }}</div>
							<div class="item" data-value="tinymce_bbcode">{{ __('TinyMCE + BBCode') }}</div>
						</div>
					</div>
				</div>

				<div class="field" id="recently_viewed_items">
					<label>{{ __('Enable recently viewed items') }}</label>
					<div class="ui dropdown floating selection">
						<input type="hidden" name="recently_viewed_items" value="{{ old('recently_viewed_items', $settings->recently_viewed_items ?? '0') }}">
						<div class="default text">...</div>
						<div class="menu">
							<div class="item" data-value="1">{{ __('Yes') }}</div>
							<div class="item" data-value="0">{{ __('No') }}</div>
						</div>
					</div>
				</div>

				<div class="field" id="report_errors">
					<label>{{ __('Report errors') }}</label>
					<div class="ui dropdown floating selection">
						<input type="hidden" name="report_errors" value="{{ old('report_errors', $settings->report_errors ?? '1') }}">
						<div class="default text">...</div>
						<div class="menu">
							<div class="item" data-value="1">{{ __('Yes') }}</div>
							<div class="item" data-value="0">{{ __('No') }}</div>
						</div>
					</div>
				</div>

				<div class="field">
					<label>{{ __('Notification for users') }}</label>
					<input type="text" name="users_notif" value="{{ old('users_notif', $settings->users_notif ?? null ) }}">
					<small><i class="circular exclamation small red icon"></i>{{ __('A text alert informing users about anything.') }}</small>
				</div>

				<div class="field">
					<label>{{ __('Counters') }}<sup>{{ __('To show on the footer of the page.') }}</sup></label>
					<div class="ui dropdown floating multiple search selection">
						<input type="hidden" name="counters" value="{{ old('counters', $settings->counters ?? '') }}">
						<div class="default text">...</div>
						<div class="menu">
							<div class="item" data-value="products">{{ __('Products') }}</div>
							<div class="item" data-value="categories">{{ __('Categories') }}</div>
							<div class="item" data-value="online_users">{{ __('Online users') }}</div>
							<div class="item" data-value="orders">{{ __('Orders') }}</div>
							<div class="item" data-value="affiliate_earnings">{{ __('Affiliate earnings') }}</div>
						</div>
					</div>
				</div>

				<div class="field">
					<label>{{ __('Fake counters') }}<sup></label>
					<div class="ui dropdown floating selection">
						<input type="hidden" name="fake_counters" value="{{ old('fake_counters', $settings->fake_counters ?? '1') }}">
						<div class="default text">...</div>
						<div class="menu">
							<div class="item" data-value="1">{{ __('Yes') }}</div>
							<div class="item" data-value="0">{{ __('No') }}</div>
						</div>
					</div>
				</div>
			</div>

			<div class="tab" data-tab="facebook_pixel">
				<div class="field">
					<label>{{ __('Facebook pixel') }}</label>
					<textarea name="facebook_pixel" id="" cols="30" rows="10" placeholder="{{ __('Enter your pixel code here') }}">{{ old('facebook_pixel', $settings->facebook_pixel ?? null ) }}</textarea>
				</div>
			</div>

			<div class="tab" data-tab="traffic_security">
				<div class="field">
					<label>{{ __('Authorized bots and crawlers') }}</label>
					<input type="text" name="authorized_bots" placeholder="google, yandex, ..." value="{{ old('authorized_bots', $settings->authorized_bots ?? null) }}">
				</div>

				<div class="field">
					<label>{{ __('Allowed user views per minute') }}</label>
					<input type="number" name="user_views_per_minute" placeholder="..." value="{{ old('user_views_per_minute', $settings->user_views_per_minute ?? null) }}">
				</div>
			</div>
		</div>
	</div>
</form>

<script>
	'use strict';

	$(function()
	{
		let query = queryString.parse(location.search);

		if(query.tab !== undefined)
		{
			if(!$(`a[data-tab="${query.tab}"]`).length)
				return;

			$(`#settings.general .ui.menu .item[data-tab="${query.tab}"]`).click();
			$('#settings.general .ui.menu').animate({scrollLeft: $(`a[data-tab="${query.tab}"]`).position().left - 80}, 500, ()=>
			{
				$('#settings.general .column > .nav > .item').attr('data-scrollpos', parseInt($('#settings.general .ui.menu').scrollLeft()))
			});
		}

		$('#update_products_extension button').on('click', function()
		{
			$(this).toggleClass('active loading', true)
			
			$.get('/admin/settings/update_products_extension')
			.done(data =>
			{
				alert('{{ __('Done') }}');
			})
			.always(() =>
			{
				$(this).toggleClass('active loading', false)
			})
		})

		$('#settings.general .column > .nav > .item').on('mousedown mouseup', function(e)
		{
			let maxScroll = $('#settings.general .ui.menu')[0].scrollWidth - $('#settings.general .ui.menu')[0].offsetWidth;
			let scrollPos = parseInt($(this).attr('data-scrollpos'));

			if(e.type === 'mousedown')
			{
				window.scrollGeneralSets = setInterval(()=>
				{
					if(scrollPos > maxScroll)
					{
						scrollPos = maxScroll;
					}
					else
					{
						if($(this).hasClass('right'))
						{
							scrollPos += scrollPos < maxScroll ? 1 : 0;
						}
						else
						{
							scrollPos -= scrollPos > 0 ? 1 : 0;	
						}
					}

					$('#settings.general .column > .nav > .item').attr('data-scrollpos', scrollPos);

					$('#settings.general .ui.menu').animate({
						scrollLeft: scrollPos
					}, 0);
				}, 0)
			}
			else // mouseup
			{
				clearInterval(scrollGeneralSets)
 			}
		})


		$('#generate-fake-profiles').on('click', function()
		{
			$(this).toggleClass('disabled loading', true);

			let country = $('input[name="fake_profiles[country]"]').val();
			let gender 	= $('input[name="fake_profiles[gender]"]').val();
			let count 	= $('input[name="fake_profiles[count]"]').val();

			$.post('/admin/generate_fake_profiles', {country, gender, count})
			.done(data =>
			{
				alert(data.message)
			})
			.always(() =>
			{
				$(this).toggleClass('disabled loading', false);
			})
		})

		
		$('#list-fake-profiles').on('click', function()
		{
			$(this).toggleClass('disabled loading', true);

			$.get('/admin/list_fake_profiles')
			.done(data =>
			{
				let profiles = [];

				for(let i in data.profiles)
				{
					profiles.push(`
						<div class="item">
							<img src="/storage/profiles/${data.profiles[i].avatar}?t=${new Date().getTime()}">
							<div class="content capitalize">
								<div class="name">${data.profiles[i].name}</div>
								<div class="country">(${data.profiles[i].country})</div>
							</div>
							<i class="close icon mr-0" data-id="${i}"></i>
						</div>
					`)					
				}

				$('#profiles-list').modal('show').find('.content').html(profiles.join(''))
			})
			.always(() =>
			{
				$(this).toggleClass('disabled loading', false);
			})
		})


		$(document).on('click', '#profiles-list .content .item i', function()
		{
			let id = $(this).data('id');

			$.post('/admin/delete_fake_profiles', {id})
			.done(data =>
			{
				let profiles = [];

				for(let i in data.profiles)
				{
					profiles.push(`
						<div class="item">
							<img src="/storage/profiles/${data.profiles[i].avatar}?t=${new Date().getTime()}">
							<div class="content capitalize">
								<div class="name">${data.profiles[i].name}</div>
								<div class="country">(${data.profiles[i].country})</div>
							</div>
							<i class="close icon mr-0" data-id="${i}"></i>
						</div>
					`)					
				}

				$('#profiles-list').modal('show').find('.content').html(profiles.join(''))
			})
		})

		

		$('input[name="maintenance[enabled]"]').on('change', function()
		{
			$('.maintenance-info').toggleClass('d-none', $(this).val() === '0')
		})

		$('#settings input, #settings textarea').on('keydown', function(e) 
		{
		    if((e.which == '115' || e.which == '83' ) && (e.ctrlKey || e.metaKey))
		    {		        
		        $('form.main').submit();

		  			e.preventDefault();

		        return false;
		    }
		    else
		    {
		        return true;
		    }
		})

		$(document).on('keyup', '.items-search input.search', debounce(function(e)
		{
			var _this = $(e.target);

			var val = _this.val().trim();

			if(!val.length)
				return;

			$.post('{{ route('products.api') }}', {'keywords': val, where: {'type': 'audio'}}, null, 'json')
			.done(function(res)
			{
				var items = res.products.reduce(function(carry, item)
										{
											carry.push('<a class="item" data-value="'+item.id+'|'+item.preview+'">'+item.name+'</a>');
											return carry;
										}, []);

				_this.closest('.items-search').find('.menu').html(items.join(''));
			})
		}, 200));

		$('input[name="template"]').on('change', function()
		{
			var template = $(this).val().trim();

			$('.homepage-items .table.' + template)
			.toggleClass('d-none', false)
			.siblings('.table').toggleClass('d-none', true);

			$('#template-mode, #categories-on-homepage').toggleClass('d-none', template != 'axies');
			$('#product-card-cover-mask').toggleClass('d-none', template != 'tendra');
		})

	  $('.summernote').summernote({
	    placeholder: '{{ __('Content') }}',
	    tabsize: 2,
	    height: 100,
	    tooltip: false
	  });
	})
</script>

@endsection