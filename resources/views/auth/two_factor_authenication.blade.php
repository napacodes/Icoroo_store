<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="UTF-8">
    <meta name="language" content="{{ str_replace('_', '-', app()->getLocale()) }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ asset_("storage/images/".config('app.favicon'))}}">
    <title>{!! config('meta_data.title') !!}</title>    

    {{-- CSRF Token --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

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

    {{-- translations --}}
    <script type="application/javascript" src="/translations.js"></script>

    <style>
      {!! load_font() !!}
    </style>

    {{-- jQuery --}}  
    <script type="application/javascript" src="/assets/jquery-3.6.0.min.js"></script>
    
    {{-- Js-Cookie --}}
    <script type="application/javascript" src="/assets/js.cookie.min.js"></script>
    
    {{-- JQuery-UI --}}
    <script type="application/javascript" src="/assets/jquery-ui-1.13.1/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="/assets/jquery-ui-1.13.1/jquery-ui.min.css">
    
    {{-- Base64 Encode / Decode --}}
    <script type="application/javascript" src="/assets/base64.min.js"></script>

    {{-- Semantic-UI --}}
    <link rel="stylesheet" href="/assets/semantic-ui/semantic.min.2.4.2-{{ locale_direction() }}.css">
    <script type="application/javascript" src="/assets/semantic-ui/semantic.min.2.4.2.js"></script>

    {{-- Spacing CSS --}}
    <link rel="stylesheet" href="/assets/css-spacing/spacing-{{ locale_direction() }}.css">

    {{-- App CSS --}}
    <link rel="stylesheet" href="{{ asset_('assets/front/'.config('app.template', 'valexa').'-'.locale_direction().'.css?v='.config('app.version')) }}">
    
    {{-- Query string --}}
    <script type="application/javascript" src="/assets/query-string.min.js"></script>

    @if(config('app.json_ld'))
    <script type="application/ld+json">
    {!! @json_encode(config('json_ld', []), JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}
    </script>
    @endif

    <script type="application/javascript" src="/props.js"></script>

    <style>
      body, html {
        height: 100vh !important;
      }
      
      .main.container {
        height: 100%;
        display: contents;
        padding-top: 0 !important;
      }

      .grid {
        min-height: 100%;
      }

      .form.column {
        width: 400px !important;
      }
    </style>

    <script>
      'use strict';

      $(() => 
      {
          $('#two-factor-auth .body .input input').on('keyup', function(e)
          {
            if(/^\d+$/.test(e.key))
            {
              if($(this).next('input').length)
              {
                  $(this).next('input').focus()
              }
            }
          })
      })
    </script> 
    
    @yield('additional_head_tags')

  </head>
  <body dir="{{ locale_direction() }}">
    <div class="ui main fluid container pt-0" id="app">
      <div class="ui one column celled middle aligned grid m-0 shadowlessn" id="two-factor-auth">
        <form class="ui form large column mx-auto" action="{{ route('two_factor_authentication', ['redirect' => request()->redirect ?? '/', '2fa_sec' => request()->query('2fa_sec'), 'redirect' => request()->query('redirect')]) }}" method="post">
          @csrf
          <input type="hidden" name="2fa_sec" value="{{ request()->query('2fa_sec') }}">
          <div class="ui fluid card">
            <div class="content header">
              {{ config('meta_data.title') }}
            </div>
            <div class="content body">
              <div class="header">
                {{ __('Security Verification') }}
              </div>
              @if($qrCodeUrl)
              <div class="text">
                {!! __("Please scan this QR code using :name and enter the provided code in the field below", ['name' => '<a class="text" href="//play.google.com/store/apps/details?id=com.google.android.apps.authenticator2" target="_blank">'.__('Google Authenticator App').'</a>']) !!}
              </div>
              <div class="qr_code">
                <img src="{{ $qrCodeUrl }}">
              </div>
              @else
              <div class="text mb-1">
                {!! __("Please open Google Authenticator App and enter the authentification code in the field below") !!}
              </div>
              @endif
              <div class="field">
                 @if($qrCodeUrl)
                <label>{{ __('Enter the code :') }}</label>
                @endif
                <div class="input">
                  <input type="text" required name="verification_code[]" maxlength="1">
                  <input type="text" required name="verification_code[]" maxlength="1">
                  <input type="text" required name="verification_code[]" maxlength="1">
                  <input type="text" required name="verification_code[]" maxlength="1">
                  <input type="text" required name="verification_code[]" maxlength="1">
                  <input type="text" required name="verification_code[]" maxlength="1">
                </div>
              </div>
            </div>
            <div class="content footer">
              <button class="ui purple fluid button" type="submit">{{ __('Submit') }}</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </body>
</html>
