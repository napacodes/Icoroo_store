@extends('back.master')

@section('title', __('Cache settings'))


@section('content')

<div class="ui large main form" spellcheck="false" id="app">
	<div class="table wrapper">
		<table class="ui table">
			<tbody>
				<tr>
					<td class="px-2">{{ __('Cached template files') }}</td>
					<td class="right aligned pr-2"><button class="ui red large circular button rounded mx-0 clear-cache" data-name="view">{{ __('Clear') }}</button></td>
				</tr>

				<tr>
					<td class="px-2">{{ __('Cached tokens and other data') }}</td>
					<td class="right aligned pr-2"><button class="ui red large circular button rounded mx-0 clear-cache" data-name="cache">{{ __('Clear') }}</button></td>
				</tr>

				<tr>
					<td class="px-2">{{ __('Users sessions') }}</td>
					<td class="right aligned pr-2"><button class="ui red large circular button rounded mx-0 clear-cache" data-name="session">{{ __('Clear') }}</button></td>
				</tr>
			</tbody>
		</table>
	</div>
</div>

<script type="application/javascript">
	'use strict';

	$(() => 
	{
		$('.clear-cache').on('click', function()
		{
			let name = $(this).data('name');

			if(!/^view|cache|session$/i.test(name))
			{
				return;
			}

			$.post('/admin/clear_cache', {name})
			.done(function(data)
			{
				alert('{{ __('Done') }}')
			})
		})
	})
</script>
@endsection