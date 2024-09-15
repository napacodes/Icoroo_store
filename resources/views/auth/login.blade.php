@extends('auth.master')

@section('additional_head_tags')
<title>{{ __('Login') }}</title>

@if(captcha_is_enabled() && captcha_is('google'))
{!! google_captcha_js() !!}
@endif
@endsection

@section('title', __('Login to your account'))


@section('content')
<div class="content">
  @if(array_filter(array_column(config('services'), 'enabled')))
  <div class="ui floating dropdown right labeled icon fluid large basic button">
    <div class="text">{{ __('With your social account') }}</div>
    <i class="dropdown icon"></i>
    <div class="menu">
      @if(config('services.github.enabled'))
      <a href="{{ route('social_account.login', ['provider' => 'github']) }}" class="item">
        <i class="github icon"></i>
        {{ __('Github') }}
      </a>
      @endif

      @if(config('services.google.enabled'))
      <a href="{{ route('social_account.login', ['provider' => 'google']) }}" class="item">
        <i class="google icon"></i>
        {{ __('Google') }}
      </a>
      @endif

      @if(config('services.twitter.enabled'))
      <a href="{{ route('social_account.login', ['provider' => 'twitter']) }}" class="item">
        <i class="twitter icon"></i>
        {{ __('Twitter') }}
      </a>
      @endif

      @if(config('services.dribbble.enabled'))
      <a href="{{ route('social_account.login', ['provider' => 'dribbble']) }}" class="item">
        <i class="dribbble icon"></i>
        {{ __('Dribbble') }}
      </a>
      @endif

      {{-- @if(config('services.tiktok.enabled'))
      <a href="{{ route('social_account.login', ['provider' => 'tiktok']) }}" class="item">
        <i class="tiktok icon"></i>
        TikTok
      </a>
      @endif

      @if(config('services.reddit.enabled'))
      <a href="{{ route('social_account.login', ['provider' => 'reddit']) }}" class="item">
        <i class="reddit icon"></i>
        Reddit
      </a>
      @endif --}}

      @if(config('services.facebook.enabled'))
      <a href="{{ route('social_account.login', ['provider' => 'facebook']) }}" class="item">
        <i class="facebook icon"></i>
        {{ __('Facebook') }}
      </a>
      @endif

      @if(config('services.linkedin.enabled'))
      <a href="{{ route('social_account.login', ['provider' => 'linkedin']) }}" class="item">
        <i class="linkedin icon"></i>
        {{ __('Linkedin') }}
      </a>
      @endif

      @if(config('services.vkontakte.enabled'))
      <a href="{{ route('social_account.login', ['provider' => 'vkontakte']) }}" class="item">
        <i class="vk icon"></i>
        {{ __('Vkontakte (VK)') }}
      </a>
      @endif
    </div>
  </div>

  <div class="ui horizontal divider">{{ __('Or') }}</div>
  @endif
  
  <form class="ui large form" action="{{ route('login', ['redirect' => request()->redirect ?? '/']) }}" method="post">
    @csrf 

    <div class="field">
      <label>{{ __('Email') }}</label>
      <input type="email" placeholder="..." name="email" value="{{ old('email', session('email')) }}" required autocomplete="email" autofocus>

      @error('email')
        <div class="ui negative message">
          <strong>{{ $message }}</strong>
        </div>
      @enderror
    </div>

    <div class="field">
      <label>{{ __('Password') }}</label>
      <input type="password" placeholder="..." name="password" required autocomplete="current-password">

      @error('password')
        <div class="ui negative message">
          <strong>{{ $message }}</strong>
        </div>
      @enderror
    </div>

    <div class="field">
      <div class="ui checkbox">
        <input type="checkbox" name="remember" id="remember">
        <label class="checkbox" for="remember">{{ __('Remember me') }}</label>
      </div>
    </div>

    @error('captcha')
      <div class="ui negative message">
        <strong>{{ $message }}</strong>
      </div>
    @enderror

    @error('g-recaptcha-response')
      <div class="ui negative message">
        <strong>{{ $message }}</strong>
      </div>
    @enderror

    @if(captcha_is_enabled('login'))
    <div class="field d-flex justify-content-center">
      {!! render_captcha() !!}

      @if(captcha_is('mewebstudio'))
        <input type="text" name="captcha" value="{{ old('captcha') }}" class="ml-1">
      @endif
    </div>
    @endif

    <div class="field mb-0">
      <button class="ui yellow large fluid circular button" type="submit">{{ __('Login') }}</button>
    </div>

    <div class="field">
      <div class="ui text menu my-0">
        <a class="item right aligned" href="{{ route('password.request') }}">{{ __('Forgot password') }}</a>
      </div>
    </div>
  </form>
</div>

<div class="content center aligned">
  <p>{{ __('Don\'t have an account') }} ?</p>
  <a href="{{ route('register') }}" class="ui fluid large blue circular button">{{ __('Create an account') }}</a>
</div>

<script>
    'use strict';
    
    $(function()
    {
        $('.ui.dropdown').dropdown();
    })
</script>
@endsection
