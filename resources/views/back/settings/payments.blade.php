@extends('back.master')

@section('title', __('Payments settings'))

@section('additional_head_tags')

@if(config('app.html_editor') == 'summernote')
<link href="{{ asset_('assets/admin/summernote-lite-0.8.12.css') }}" rel="stylesheet">
<script src="{{ asset_('assets/admin/summernote-lite-0.8.12.js') }}"></script>
@else
<script src="{{ asset_('assets/tinymce_5.9.2/js/jquery.tinymce.min.js') }}"></script>
<script src="{{ asset_('assets/tinymce_5.9.2/js/tinymce.min.js') }}"></script>
@endif

<link rel="stylesheet" href="/assets/jquery-ui-1.13.1/jquery-ui.min.css">
<script type="application/javascript" src="/assets/jquery-ui-1.13.1/jquery-ui.min.js"></script>

@endsection

@section('content')

<form class="ui large main form" method="post" spellcheck="false" action="{{ route('settings.update', 'payments') }}">

	<div class="field">
		<button type="submit" class="ui large circular labeled icon button mx-0">
		  <i class="save outline icon mx-0"></i>
		  {{ __('Update') }}
		</button>

		<button type="button" class="ui basic large circular button mr-0 ml-1-hf" id="disable-all-services">
			{{ __('Disable all services') }}
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
	
	<div class="one column grid" id="settings">
		<div class="ui three doubling stackable cards payment-gateways">
			@foreach(config('payment_gateways', []) as $gateway_name => $gateway_config)
			<div class="fluid card" data-slug="{{ $gateway_config['slug'] }}" id="{{ $gateway_config['name'] }}">
				<div class="content">
					<h3 class="header">
						<a href="{{ $gateway_config['url'] ?? null }}" target="_blank"><img loading="lazy" src="{{ $gateway_config['fields']['icon']['value'] ?? null }}" alt="{{ $gateway_config['name'] }}" class="ui small avatar mr-1">{{ __($gateway_config['name']) }}</a>
						<input type="hidden" name="gateways[{{ $gateway_name }}][name]" value="{{ $gateway_name }}">

						<div class="checkbox-wrapper">
							<div class="ui fitted toggle checkbox">
						    <input 
						    	type="checkbox" 
						    	name="gateways[{{ $gateway_name }}][enabled]"
						    	@if(!empty(old("gateways.{$gateway_name}.enabled")))
									{{ old("gateways.{$gateway_name}.enabled") ? 'checked' : '' }}
									@else
									{{ ($settings->gateways->$gateway_name->enabled ?? null) ? 'checked' : '' }}
						    	@endif
						    >
						    <label></label>
						  </div>
						</div>
					</h3>
				</div>

				<div class="content">
					@foreach(collect($gateway_config['fields'])->except(['enabled'])->all() as $field_name => $field_config)
						@if($field_config['type'] == 'hidden')
						<input type="hidden" name="gateways[{{ $gateway_name }}][{{ $field_name }}]" value="{{ $field_config['value'] ?? $settings->gateways->$gateway_name->$field_name ?? '' }}">
						@else
						<div class="field">
							<label>{{ __(ucfirst(str_replace('_', ' ', $field_name))) }}</label>
							@if($field_config['type'] == 'string')
							<input type="text" name="gateways[{{ $gateway_name }}][{{ $field_name }}]" value="{{ old("gateways.{$gateway_name}.{$field_name}", $settings->gateways->$gateway_name->$field_name ?? null) }}">
							@elseif($field_config['type'] == 'html_editor')
							<textarea name="gateways[{{$gateway_name  }}][{{$field_name  }}]" class="html-editor" cols="30" rows="10">{{ old("gateways.{$gateway_name}.{$field_name}", $settings->gateways->$gateway_name->$field_name ?? null)}}</textarea>
							@elseif($field_config['type'] == 'dropdown')
							<div  class="ui selection floating dropdown {{ $field_config['multiple'] ? 'multiple search' : ''}}">
								<input type="hidden" name="gateways[{{ $gateway_name }}][{{ $field_name }}]" value="{{ old("gateways.{$gateway_name}.{$field_name}", $settings->gateways->$gateway_name->$field_name ?? $field_config['value']) }}">
								<div class="default text">...</div>
								<div class="menu">
									@foreach($field_config['options'] as $key => $val)
									<div class="item" data-value="{{ $key }}">{{ __($val) }}</div>
									@endforeach
								</div>
							</div>
							@endif
						</div>
						@endif
					@endforeach					
				</div>
			</div>
			@endforeach
		</div>

		<div class="ui fluid blue segment rounded-corner">
			<div class="five fields mt-1">
				<div class="field" id="vat">
					<label>{{ __('VAT') }} (%)</label>
					<input type="number" step="0.01" name="vat" value="{{ old('vat', $settings->vat ?? null) }}">
				</div>

				<div class="field" id="currency-code">
					<label>{{ __('Main currency code') }}</label>
					<input type="text" name="currency_code" value="{{ old('currency_code', $settings->currency_code ?? null) }}">
				</div>

				<div class="field" id="main-currency-symbol">
					<label>{{ __('Main currency symbol') }}</label>
					<input type="text" name="currency_symbol" value="{{ old('currency_symbol', $settings->currency_symbol ?? null) }}">
				</div>

				<div class="field" id="currency_position">
					<label>{{ __('Currency position') }} <sup>(1)</sup></label>
					<div class="ui selection floating dropdown">
						<input type="hidden" name="currency_position" value="{{ old('currency_position', $settings->currency_position ?? 'left') }}">
						<div class="text">...</div>
						<div class="menu">
							<a class="item" data-text="{{ __('Left') }}" data-value="left">{{ __('Left') }}</a>
							<a class="item" data-text="{{ __('Right') }}" data-value="right">{{ __('Right') }}</a>
						</div>
					</div>
				</div>

				<div class="field" id="allow-foreign-currencies">
					<label>{{ __('Allow foreign currencies') }} <sup>(1)</sup></label>
					<div class="ui selection floating dropdown">
						<input type="hidden" name="allow_foreign_currencies" value="{{ old('allow_foreign_currencies', $settings->allow_foreign_currencies ?? null) }}">
						<div class="text">...</div>
						<div class="menu">
							<a class="item" data-text="{{ __('Yes') }}" data-value="1">{{ __('Yes') }}</a>
							<a class="item" data-text="{{ __('No') }}" data-value="0">{{ __('No') }}</a>
						</div>
					</div>
				</div>
			</div>

			<div class="field">
				<small>(1) : {{ __('Allow receiving payments in defferent currencies than the main currency.') }}</small>
			</div>

			<div class="field" id="currencies">
				<label>{{ __('Currencies') }}</label>
				<div class="ui fluid multiple search selection floating dropdown">
					<input type="hidden" name="currencies" value="{{ strtolower(old('currencies', $settings->currencies ?? null)) }}">
					<div class="text">...</div>
					<div class="menu">
						@foreach($currencies ?? [] as $code => $currency)
						<a class="item" data-text="{{ $code }}">{{ $code }}</a>
						@endforeach
					</div>
				</div>
			</div>

			<div class="field" id="currency-exchange">
				<label>{{ __('Currency exchange API') }}</label>
				<div class="ui selection floating dropdown">
					<input type="hidden" name="exchanger" value="{{ old('exchanger', $settings->exchanger ?? null) }}">
					<div class="text"></div>
					<div class="menu">
						<a class="item"></a>
						@foreach(config('exchangers', []) as $val => $config)
						<a class="item" data-value="{{ $val }}">
							{{ $config['name'] }}
							@if($config['crypto'])
							<sup>{{ __('Supports cryptocurrency') }}</sup>
							@endif
						</a>
						@endforeach
					</div>
				</div>
			</div>
      
      @foreach(config('exchangers', []) as $name => $config)
			<div class="field exchanger_api_key" data-exchanger="{{ $name }}" id="currency-exchanger-api-key">
				<label>{{ __(':name API key', ['name' => $config['name']]) }}</label>
				<input type="text" name="exchangers[{{ $name }}][api_key]" value="{{ old("exchangers.{$name}.api_key", $settings->exchangers->$name->api_key ?? null) }}">
			</div>
			@endforeach

			<div class="field exchanger_api_key" data-exchanger="api.exchangeratesapi.io" id="currency-exchanger-api-key">
				<label>{{ __(':name API key', ['name' => 'api.exchangeratesapi.io']) }}</label>
				<input type="text" name="exchangeratesapi_io_key" value="{{ old('exchangeratesapi_io_key', $settings->exchangeratesapi_io_key ?? null) }}">
			</div>
			
			<div class="field exchanger_api_key" data-exchanger="api.currencyscoop.com" id="currency-exchanger-api-key">
				<label>{{ __(':name API key', ['name' => 'api.currencyscoop.com']) }}</label>
				<input type="text" name="currencyscoop_api_key" value="{{ old('currencyscoop_api_key', $settings->currencyscoop_api_key ?? null) }}">
			</div>

			<div class="field exchanger_api_key" data-exchanger="pro-api.coinmarketcap.com" id="currency-exchanger-api-key">
				<label>{{ __(':name API key', ['name' => 'pro-api.coinmarketcap.com']) }}</label>
				<input type="text" name="coinmarketcap_api_key" value="{{ old('coinmarketcap_api_key', $settings->coinmarketcap_api_key ?? null) }}">
			</div>
			
			<div class="field" id="allow-guest-checkout">
				<label>{{ __('Allow guest checkout') }} <sup>(3)</sup></label>
				<div class="ui fluid selection floating dropdown">
					<input type="hidden" name="guest_checkout" value="{{ old('guest_checkout', $settings->guest_checkout ?? null)}}">
					<div class="text">...</div>
					<div class="menu">
						<a class="item" data-value="1">{{ __('Yes') }}</a>
						<a class="item" data-value="0">{{ __('No') }}</a>
					</div>
				</div>
				<small>(3) {{ __('Allow users to make purchases without being logged in.') }}</small>
			</div>

			<div class="two fields" id="tos">
				<div class="field" title="{{ __('Terms and conditions') }}">
					<label>{{ __('Require agreement to the market TOS') }}</label>
				  	<div class="ui selection floating dropdown left-circular-corner">
						<input type="hidden" name="tos" value="{{ old('tos', $settings->tos ?? '0') }}">
						<div class="text">...</div>
						<div class="menu">
							<a class="item" data-value="1">{{ __('Yes') }}</a>
							<a class="item" data-value="0">{{ __('No') }}</a>
						</div>
					</div>
				</div>
				<div class="field">
					<label>{{ __('TOS page URL') }}</label>
					<input type="text" name="tos_url" value="{{ old('tos_url', $settings->tos_url ?? '/page/terms-and-conditions') }}">
				</div>
			</div>

			<div class="field" id="buyer-note">
				<label>{{ __('Allow buyers to add notes on orders') }}</label>
			  <div class="ui selection floating dropdown left-circular-corner">
					<input type="hidden" name="buyer_note" value="{{ old('buyer_note', $settings->buyer_note ?? '0')}}">
					<div class="text">...</div>
					<div class="menu">
						<a class="item" data-value="1">{{ __('Yes') }}</a>
						<a class="item" data-value="0">{{ __('No') }}</a>
					</div>
				</div>
				<small>{{ __('Allow buyers to enter some instructions about their purchase (customization, ...etc.).') }}</small>
			</div>

			<div class="two fields" id="pay-what-you-want">
				<div class="field">
					<label>{{ __('Enable « Pay What You Want »') }} <sup>(4)</sup></label>
				  <div class="ui selection floating dropdown left-circular-corner">
						<input type="hidden" name="pay_what_you_want[enabled]" value="{{ old('pay_what_you_want.enabled', $settings->pay_what_you_want->enabled ?? '0')}}">
						<div class="text">...</div>
						<div class="menu">
							<a class="item" data-value="1">{{ __('Yes') }}</a>
							<a class="item" data-value="0">{{ __('No') }}</a>
						</div>
					</div>
				</div>
				<div class="field">
					<label>{{ __('For') }}</label>
					<div class="ui selection multiple floating dropdown right-circular-corner">
						<input type="hidden" name="pay_what_you_want[for]" value="{{ old('pay_what_you_want.for', $settings->pay_what_you_want->for ?? null)}}">
						<div class="text">...</div>
						<div class="menu">
							<a class="item" data-value="products">{{ __('Products') }}</a>
							<a class="item" data-value="subscriptions">{{ __('Subscriptions') }}</a>
						</div>
					</div>
				</div>
			</div>
			
			<small>(4) {{ __('Allow users to pay what they want for products with an optional minimum amount.') }}</small>

			<div class="field mt-1">
				<label>{{ __('Change user currency based on his country') }}</label>
			  <div class="ui selection floating dropdown left-circular-corner">
					<input type="hidden" name="currency_by_country" value="{{ old('currency_by_country', $settings->currency_by_country ?? '0')}}">
					<div class="text">...</div>
					<div class="menu">
						<a class="item" data-value="1">{{ __('Yes') }}</a>
						<a class="item" data-value="0">{{ __('No') }}</a>
					</div>
				</div>
			</div>

			<div class="field mt-1">
				<label>{{ __('Enable add to cart') }}</label>
			  <div class="ui selection floating dropdown left-circular-corner">
					<input type="hidden" name="enable_add_to_cart" value="{{ old('enable_add_to_cart', $settings->enable_add_to_cart ?? '1')}}">
					<div class="text">...</div>
					<div class="menu">
						<a class="item" data-value="1">{{ __('Yes') }}</a>
						<a class="item" data-value="0">{{ __('No') }}</a>
					</div>
				</div>
			</div>

			<div class="field mt-1">
				<label>{{ __('Enable webhooks if applicable') }}</label>
			  <div class="ui selection floating dropdown left-circular-corner">
					<input type="hidden" name="enable_webhooks" value="{{ old('enable_webhooks', $settings->enable_webhooks ?? '1') }}">
					<div class="text">...</div>
					<div class="menu">
						<a class="item" data-value="1">{{ __('Yes') }}</a>
						<a class="item" data-value="0">{{ __('No') }}</a>
					</div>
				</div>
			</div>
			
			<div class="field mt-1">
				<label title="{{ __('Divide prices by 1000 to reduce the lenght') }}">{{ __('Show prices in K format') }}</label>
			  <div class="ui selection floating dropdown left-circular-corner">
					<input type="hidden" name="show_prices_in_k_format" value="{{ old('show_prices_in_k_format', $settings->show_prices_in_k_format ?? '1') }}">
					<div class="text">...</div>
					<div class="menu">
						<a class="item" data-value="1">{{ __('Yes') }}</a>
						<a class="item" data-value="0">{{ __('No') }}</a>
					</div>
				</div>
			</div>

			<div class="field" id="invoice">
				<label>{{ __('Auto check & update pending transactions') }}</label>
				<div class="ui dropdown floating selection">
					<input type="hidden" name="update_pending_transactions" value="{{ old('update_pending_transactions', $settings->update_pending_transactions ?? '1') }}">
					<div class="default text">...</div>
					<div class="menu">
						<div class="item" data-value="1">{{ __('Yes') }}</div>
						<div class="item" data-value="0">{{ __('No') }}</div>
					</div>
				</div>
				<small>{{ __("This can be used for payment services that don't support webhook or IPN") }}</small>
			</div>

			<div class="field mt-1">
				<label>{{ __('Delete pending orders in x hours') }}</label>
			  <input type="number" name="delete_pending_orders" value="{{ old('delete_pending_orders', $settings->delete_pending_orders ?? null) }}">
			</div>

			<small>(5) : {{ __('Change the currency to this currency when a user proceed to the checkout, regardless his selected currrency.') }}</small>
		</div>
	</div>
</form>

<script>
	'use strict';

	$(function()
  {
  	$("#settings .payment-gateways").sortable(
		{
			update: (event, ui) =>
			{
				window.order = {};

				$("#settings .payment-gateways .card").each(function(index)
				{
					order[$(this).data('slug')] = index+1;

					$(`input[name="gateways[${$(this).data('slug')}][order]"]`, this).val(index+1)
				})
			}
		})


	  $('#disable-all-services').on('click', function()
	  {
	  	$('#settings input[type="checkbox"]').prop('checked', false);
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

		let currencyExchangeApi = $('input[name="exchanger"]').val();
		let currencyExchangers = @json(array_keys(config('exchangers', [])));

		if(currencyExchangers.indexOf(currencyExchangeApi) >= 0)
		{
			$(`.exchanger_api_key[data-exchanger="${currencyExchangeApi}"]`).show().siblings('.exchanger_api_key').hide();
		}
		else
		{
			$('.exchanger_api_key').hide();
		}

		$('input[name="exchanger"]').on('change', function()
		{
			if(currencyExchangers.indexOf($(this).val().trim()) >= 0)
			{
				$(`.exchanger_api_key[data-exchanger="${$(this).val().trim()}"]`).show().siblings('.exchanger_api_key').hide();
			}	
			else
			{
				$('.exchanger_api_key').hide();		
			}
		})
	})
</script>

<script>
	'use strict';

	@if(config('app.html_editor') == 'summernote')
	$('.html-editor').summernote({
    tabsize: 2,
    height: 200,
    tooltip: false
  	});
	@else
		window.tinyMceOpts = {
	  plugins: 'print preview paste importcss searchreplace autolink autosave save directionality code visualblocks visualchars fullscreen image link media template codesample table charmap hr pagebreak nonbreaking anchor toc insertdatetime advlist lists wordcount imagetools textpattern noneditable help charmap quickbars emoticons' /* bbcode */,
	  imagetools_cors_hosts: ['picsum.photos'],
	  menubar: 'file edit view insert format tools table help',
	  toolbar: 'undo redo | bold italic underline strikethrough | fontselect fontsizeselect formatselect | alignleft aligncenter alignright alignjustify | outdent indent |  numlist bullist | forecolor backcolor removeformat | pagebreak | charmap emoticons | fullscreen  preview save print | insertfile image media template link anchor codesample | ltr rtl',
	  toolbar_sticky: true,
	  autosave_ask_before_unload: true,
	  autosave_interval: '30s',
	  autosave_prefix: '{path}{query}-{id}-',
	  autosave_restore_when_empty: false,
	  autosave_retention: '2m',
	  image_advtab: true,
	  link_list: [
	    { title: 'My page 1', value: 'https://www.tiny.cloud' },
	    { title: 'My page 2', value: 'http://www.moxiecode.com' }
	  ],
	  image_list: [
	    { title: 'My page 1', value: 'https://www.tiny.cloud' },
	    { title: 'My page 2', value: 'http://www.moxiecode.com' }
	  ],
	  image_class_list: [
	    { title: 'None', value: '' },
	    { title: 'Some class', value: 'class-name' }
	  ],
	  importcss_append: true,
	  file_picker_callback: function (callback, value, meta) {
	    /* Provide file and text for the link dialog */
	    if (meta.filetype === 'file') {
	      callback('https://www.google.com/logos/google.jpg', { text: 'My text' });
	    }

	    /* Provide image and alt text for the image dialog */
	    if (meta.filetype === 'image') {
	      callback('https://www.google.com/logos/google.jpg', { alt: 'My alt text' });
	    }

	    /* Provide alternative source and posted for the media dialog */
	    if (meta.filetype === 'media') {
	      callback('movie.mp4', { source2: 'alt.ogg', poster: 'https://www.google.com/logos/google.jpg' });
	    }
	  },
	  templates: [
	        { title: 'New Table', description: 'creates a new table', content: '<div class="mceTmpl"><table width="98%%"  border="0" cellspacing="0" cellpadding="0"><tr><th scope="col"> </th><th scope="col"> </th></tr><tr><td> </td><td> </td></tr></table></div>' },
	    { title: 'Starting my story', description: 'A cure for writers block', content: 'Once upon a time...' },
	    { title: 'New list with dates', description: 'New List with dates', content: '<div class="mceTmpl"><span class="cdate">cdate</span><br /><span class="mdate">mdate</span><h2>My List</h2><ul><li></li><li></li></ul></div>' }
	  ],
	  template_cdate_format: '[Date Created (CDATE): %m/%d/%Y : %H:%M:%S]',
	  template_mdate_format: '[Date Modified (MDATE): %m/%d/%Y : %H:%M:%S]',
	  height: 200,
	  image_caption: true,
	  quickbars_selection_toolbar: 'bold italic | quicklink h2 h3 blockquote quickimage quicktable',
	  noneditable_noneditable_class: 'mceNonEditable',
	  toolbar_mode: 'sliding',
	  contextmenu: 'link image imagetools table',
	  skin: 'oxide',
	  content_css: 'default',
	  content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }'
	};

	@if(config('app.html_editor') == 'tinymce_bbcode')
	{
		tinyMceOpts.plugins += ' bbcode';
	}
	@endif

	tinymce.init(Object.assign(tinyMceOpts, {selector: '.html-editor'}));
  @endif
</script>
@endsection