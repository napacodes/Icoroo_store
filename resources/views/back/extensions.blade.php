@extends('back.master')

@section('title', __("Extensions"))


@section('content')

<div class="row main" id="extensions">

	<div class="ui menu shadowless">
		<div class="right menu mr-1">
			<div id="search" class="ui transparent icon input item">
	      <input class="prompt" type="text" name="keywords" placeholder="Search ..." required>
	      <i class="search link icon" onclick="$('#search').submit()"></i>
	    </div>
	  </div>
	</div>

	@if($errors->any())
      @foreach ($errors->all() as $error)
         <div class="ui negative bold circular-corner fluid message">
         	<i class="times icon close"></i>
         	{{ $error }}
         </div>
      @endforeach
	@endif

	@if(session('message'))
	<div class="ui fluid small message">
	 	<i class="times icon close"></i>
	 	{{ session('message') }}
	</div>
	@endif

	<div class="table wrapper items extensions">
		<table class="ui unstackable celled table">
			<thead>
				<tr>
					<th class="five columns wide">{{ __('Name') }}</th>
					<th>{{ __('Installed version') }}</th>
					<th>{{ __('Action') }}</th>
				</tr>
			</thead>
			<tbody>
				@foreach($extensions ?? [] as $extension)
				<tr>
					<td class="center aligned capitalize">{{ str_ireplace('_', ' ', $extension->name) }} <strong>V{{ $extension->version }}</strong></td>
					<td class="center aligned">{{ $extension->installed_version ?? '-' }}</td>
					<td class="center aligned">
						@if($extension->installed)
						<div class="ui dropdown center aligned basic pink button fluid circular mr-0">
							<div class="text">{{ __('Action') }}</div>
							<div class="menu left">
								<a class="item" href="{{ route('extensions.install', ['name' => $extension->name]) }}">{{ __('Update / Reinstall') }}</a>
								<a class="item" href="{{ route('extensions.uninstall', ['name' => $extension->name, 'redirect' => 1]) }}">{{ __('Uninstall') }}</a>
							</div>
						</div>
						@else
						<a class="ui small circular button teal fluid mx-0" href="{{ route('extensions.install', ['name' => $extension->name]) }}">
							{{ __('Install') }}
						</a>
						@endif
					</td>
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>

	<form class="ui modal small license form" action="{{ route('extensions.register') }}" method="post">
		<div class="header">{{ __('Enter license key') }}</div>

		<div class="content">
			<textarea name="license_key" cols="30" rows="5" required placeholder="{{ __('Enter your license key here...') }}"></textarea>
		</div>

		<div class="actions">
			<button class="ui button yellow circular cancel" type="button">{{ __('Close') }}</button>
			<button class="ui button purple circular confirm">{{ __('Submit') }}</button>
		</div>
	</form>
</div>

<script>
	'use strict';
	$(function()
	{
		$('#search').on('submit', function(event)
		{
			if(!$('input', this).val().trim().length)
			{
				e.preventDefault();
				return false;
			}
		})

		$('#extensions .ui.dropdown').dropdown({
			action: "nothing"
		})
	})
</script>
@endsection