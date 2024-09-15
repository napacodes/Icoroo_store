@extends('back.master')

@section('title', __('Search engines settings'))


@section('content')

<form class="ui large main form" method="post" spellcheck="false" action="{{ route('settings.update', 'search_engines') }}">

	<div class="field">
		<button type="submit" class="ui large circular labeled icon button mx-0">
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

	<div class="one column grid" id="settings">
		<div class="column">
			<div class="field">
				<label>{{ __('IndexNow API key') }} <sup>(<a href="//www.bing.com/indexnow#generateApiKey" target="_blank">{{ __('Get API key') }}</a>)</sup></label>
				<input type="text" name="indexnow_key" placeholder="..." value="{{ old('indexnow_key', $settings->indexnow_key ?? null) }}">
			</div>

			<div class="field">
				<label><a href="https://en.wikipedia.org/wiki/JSON-LD" target="_blank">{{ __('Enable JSON+LD') }}</a></label>
				<div class="ui dropdown floating selection">
					<input type="hidden" name="json_ld" value="{{ old('json_ld', $settings->json_ld ?? '1') }}">
					<div class="default text">...</div>
					<div class="menu">
						<div class="item" data-value="1">{{ __('Yes') }}</div>
						<div class="item" data-value="0">{{ __('No') }}</div>
					</div>
				</div>
			</div>

			<div class="field">
				<label title="{{ __('Google, Yandex, Bing, ... etc.') }}">{{ __('Site verification') }}</label>
				<textarea name="site_verification" rows="5" placeholder="{{ __('Search engines verification codes') }} ...">{{ old('site_verification', $settings->site_verification ?? null) }}</textarea>
			</div>

			<div class="field">
				<label>{{ __('Analytics code') }}</label>
				<textarea name="analytics_code" cols="30" rows="5" placeholder="{{ __('Google analytics, Yandex analytics ...') }}">{{ old('analytics_code', $settings->analytics_code ?? null) }}</textarea>
			</div>
		
			<div class="field">
				<label>{{ __('Robots') }}</label>
				<div class="ui dropdown floating selection">
					<input type="hidden" name="robots" value="{{ old('robots', $settings->robots ?? 'follow, index') }}">
					<div class="default text">...</div>
					<div class="menu">
						<div class="item" data-value="follow, index">{{ __('Follow and Index') }}</div>
						<div class="item" data-value="follow, noindex">{{ __('Follow but do not Index') }}</div>
						<div class="item" data-value="nofollow, index">{{ __('Do not Follow but Index') }}</div>
						<div class="item" data-value="nofollow, noindex">{{ __('Do not Follow and do not Index') }}</div>
					</div>
				</div>
			</div>

		</div>
	</div>
</form>

<script>
	'use strict';

	$(function()
	{
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
	})
</script>

@endsection