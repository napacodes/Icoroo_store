@extends('back.master')

@section('title', __('Maintenance mode'))


@section('content')

<form class="ui large main form" method="post" spellcheck="false" action="{{ route('settings.update', 'maintenance') }}">

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
		<div class="field" id="maintenance[enabled]">
			<label>{{ __('Maintenance mode') }}</label>
			<div class="ui dropdown floating selection">
				<input type="hidden" name="maintenance[enabled]" value="{{ old('maintenance.enabled', $settings->enabled ?? '0') }}">
				<div class="default text">...</div>
				<div class="menu">
					<div class="item" data-value="1">{{ __('On') }}</div>
					<div class="item" data-value="0">{{ __('Off') }}</div>
				</div>
			</div>
		</div>

		<div class="mt-1 ui segment fluid red rounded-corner maintenance-info">
			<div class="field">
				<label>{{ __('IP address to exempt') }}<i class="circular tiny orange inverted exclamation icon mr-0 ml-1-hf" title="{{ __('Accept multiple IPs') }}"></i></label>
				<input type="text" name="maintenance[exception]" placeholder="192.168.50.39, 192.168.2.100" value="{{ old('maintenance.exception,', $settings->exception ?? request()->ip()) }}">
			</div>

			<div class="field">
				<label>{{ __('Expires at') }}</label>
				<input type="datetime-local" name="maintenance[expires_at]" placeholder="YYYY-MM-DD HH:mm:ss" value="{{ format_date(old('maintenance.expires_at', $settings->expires_at ?? null), 'Y-m-d\TH:i') }}">
			</div>

			<div class="field">
				<label>{{ __('Auto disable') }}</label>
				<div class="ui dropdown floating selection">
					<input type="hidden" name="maintenance[auto_disable]" value="{{ old('maintenance.auto_disable', $settings->auto_disable ?? '0') }}">
					<div class="default text">...</div>
					<div class="menu">
						<div class="item" data-value="1">{{ __('On') }}</div>
						<div class="item" data-value="0">{{ __('Off') }}</div>
					</div>
				</div>
			</div>

			<div class="field">
				<label>{{ __('Page title') }}</label>
				<input type="text" name="maintenance[title]" value="{{ old('maintenance.title', $settings->title ?? null) }}">
			</div>

			<div class="field">
				<label>{{ __('Page header') }}</label>
				<input type="text" name="maintenance[header]" value="{{ old('maintenance.header', $settings->header ?? null) }}">
			</div>

			<div class="field">
				<label>{{ __('Page subheader') }}</label>
				<input type="text" name="maintenance[subheader]" value="{{ old('maintenance.subheader', $settings->subheader ?? null) }}">
			</div>

			<div class="field">
				<label>{{ __('Page text') }}</label>
				<input type="text" name="maintenance[text]" value="{{ old('maintenance.text', $settings->text ?? null) }}">
			</div>

			<div class="field">
				<label>{{ __('Background color') }}</label>
				<div class="ui right action input">
				  <input type="text" name="maintenance[bg_color]" value="{{ old('maintenance.bg_color', $settings->bg_color ?? null) }}">
					<div class="ui blue icon button" onclick="this.nextElementSibling.click()">Color</div>
				  <input type="color" class="d-none" name="maintenance[bg_color]" value="{{ old('maintenance.bg_color', $settings->bg_color ?? null) }}">
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

		$('#check-connection').on('click', function()
		{
			$(this).addClass('loading disabled');

			var formData = $('form.main').serialize();

			$.post('{{ route('settings.check_mailer_connection') }}', formData, 'json')
			.done(data =>
			{
				alert(data.message)
			})
			.always(() =>
			{
				$(this).removeClass('loading disabled');
			})
		})
	})
</script>

@endsection