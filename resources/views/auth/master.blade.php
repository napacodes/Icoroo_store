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
    
    @yield('additional_head_tags')
  </head>

  <body dir="{{ locale_direction() }}">
    <div class="ui main fluid container pt-0" id="app">
      <div class="ui one column celled middle aligned grid m-0 shadowlessn" id="auth">
        <div class="form column mx-auto">
          <div class="ui fluid card">

            <div class="content center aligned logo">
              <a href="/">
                <img class="ui image mx-auto" src="{{ asset_("storage/images/".config('app.logo')) }}" alt="{{ config('app.name') }}">
              </a>
            </div>

            <div class="content center aligned title">
              <h2>@yield('title')</h2>
            </div>

            @yield('content')
          </div>
        </div>
      </div>
    </div>
  </body>
</html>