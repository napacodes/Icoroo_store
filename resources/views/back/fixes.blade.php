@extends('back.master')

@section('title', __('Fixes'))

@section('additional_head_tags')
<script type="application/javascript" src="/assets/base64.min.js"></script>
@endsection

@section('content')

@if(session('_message'))
<div class="ui message fluid {{ session('_status') ? 'positive' : 'negative' }}">
	<i class="close icon mr-0"></i>
	{{ session('_message') }}
</div>		
@endif

<div class="row main" id="fixes">
	<div class="table wrapper items mt-0">
		<table class="ui unstackable celled basic table center aligned">
			<thead>
				<tr>
					<th>{{ __('Version') }}</th>
					<th>{{ __('Name') }}</th>
					<th>{{ __('Release date') }}</th>
					<th>{{ __('Changelog') }}</th>
					<th>{{ __('Action') }}</th>
				</tr>
			</thead>
			<tbody>
				@if(count($fixes))
				@foreach($fixes as $fix)
				<tr>
					<td>{{ $fix->version }}</td>
					<td>{{ $fix->name }}</td>
					<td>{{ $fix->updated_at }}</td>
					<td>
						<button data-log="{!! $fix->changelog !!}" class="ui basic blue button rounded mx-0 changelog">
							{{ __('Changelog') }}
						</button>
					</td>
					<td>
						@if($fix->version <= env('APP_VERSION'))
						<button class="ui rounded grey disabled button" type="button">{{ __('Installed') }}</button>
						@else
						<form action="{{ route('fixes.install', ['id' => $fix->id, 'version' => $fix->version, 'item' => $fix->name]) }}" method="post" class="install-fix">
							<button class="ui teal rounded button mx-0 install">
								{{ __('Install') }}
							</button>
						</form>
						@endif
					</td>
				</tr>
				@endforeach
				@else
				<tr>
					<td colspan="5">{{ __('There is no fix yet.') }}</td>
				</tr>
				@endif
			</tbody>
		</table>
	</div>

	<div class="fix-log">
		<i class="close icon mx-0"></i>
		<div class="content"></div>
	</div>
</div>

<script>
	"use strict";

	$(document).on('click', '.button.changelog', function()
	{
		$('#fixes .fix-log .content').html(Base64.decode($(this).data('log'))).closest('.fix-log').show();
	})


	$(document).on('click', '.button.install', function()
	{
		$(this).toggleClass('disabled loading', true);
	})


	$(document).on('click', '#fixes .fix-log .icon', function()
	{
		$('#fixes .fix-log .content').html("").closest('.fix-log').hide();
	})
</script>
@endsection