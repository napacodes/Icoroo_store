<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
	<head>
		<meta charset="UTF-8">
		<meta name="language" content="{{ str_replace('_', '-', app()->getLocale()) }}">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link rel="icon" href="{{ asset_("storage/images/".config('app.favicon'))}}">
		
    <meta name="csrf-token" content="{{ csrf_token() }}">
		
		<script type="application/javascript" src="{{ asset_('assets/jquery-3.6.0.min.js') }}"></script>

		<script type="application/javascript" src="{{ asset('assets/jquery.countdown.min.js') }}"></script>
		
		<style>
			{!! load_font() !!}
		</style>

    <link rel="stylesheet" href="{{ asset_('assets/semantic-ui/semantic.min.2.4.2-'.locale_direction().'.css') }}">
    <script type="application/javascript" src="{{ asset_('assets/semantic-ui/semantic.min.2.4.2.js') }}"></script>

		<link rel="stylesheet" href="{{ asset_('assets/css-spacing/spacing-'.locale_direction().'.css') }}">

		<link rel="stylesheet" href="{{ asset_('assets/front/tendra-'.locale_direction().'.css?v='.config('app.version')) }}">


		<title>{{ config('app.maintenance.title') }}</title>

		@php
			$bg_color = config('app.maintenance.bg_color'); 
			$opac_color = adjustBrightness($bg_color, 0.1);
		@endphp

		<style>
			#maintenance-page {
				background: {{ $bg_color  }};
			}

			#maintenance-page .timeout-wrapper .item, #maintenance-page .more {
				background: {{ $opac_color  }};
			}
		</style>
	</head>

	<body dir="{{ locale_direction() }}">
			<div class="ui main fluid container" id="maintenance-page">
				<div class="ui celled grid m-0 shadowless">
					<div class="row">
						@if(config('app.maintenance.expires_at'))
						<div class="timeout-wrapper">
							<div class="item days">
								<div class="count">00</div>
								<div class="text">{{ __('Days') }}</div>
							</div>

							<div class="item hours">
								<div class="count">00</div>
								<div class="text">{{ __('Hours') }}</div>
							</div>

							<div class="item minutes">
								<div class="count">00</div>
								<div class="text">{{ __('Minutes') }}</div>
							</div>

							<div class="item seconds">
								<div class="count">00</div>
								<div class="text">{{ __('Seconds') }}</div>
							</div>
						</div>
						@endif

						<div class="logo">
							<img src="{{ asset_('storage/images/'.config('app.logo')) }}">
						</div>

						<div class="ui header">
							{!! config('app.maintenance.header') !!}
							
							<div class="ui sub header">
								{!! config('app.maintenance.subheader') !!}
							</div>
						</div>

						@if(config('app.maintenance.text'))
						<div class="more">
							<div class="content">
								{!! config('app.maintenance.text') !!}
							</div>
						</div>
						@endif
					</div>
				</div>
			</div>

			<script>
				$(function()
				{
						@if(config('app.maintenance.expires_at'))
						let counter = $('.timeout-wrapper');
						let finalDate = '{{ format_date(config('app.maintenance.expires_at'), "Y-m-d H:i:s") }}';
						let titles = ['days', 'hours', 'minutes', 'seconds'];

						console.log(finalDate)
					  counter.countdown(finalDate)
						.on('update.countdown', function(event)
						{
								for(let i = 0; i < titles.length; i++)
								{
									var count = event.offset[titles[i]];

									$('.timeout-wrapper .item.'+ titles[i] + ' .count').text(count > 0 ? count : '00');
								}
						})
						.on('finish.countdown', function(event)
						{
							$('.timeout-wrapper').remove()

							@if(config('app.maintenance.auto_disable'))
							location.reload();
							@endif
						});
						@endif
				})
			</script>
	</body>
</html>