@extends('back.master')

@section('title', __('Prepaid credits'))

@section('additional_head_tags')
<link rel="stylesheet" href="/assets/jquery-ui-1.13.1/jquery-ui.min.css">
<script type="application/javascript" src="/assets/jquery-ui-1.13.1/jquery-ui.min.js"></script>
@endsection

@section('content')

<div class="row main" id="prepaid_credits">

	@if(session('user_message'))
	<div class="ui fluid small message">
		<i class="icon close"></i>
		{{ session('user_message') }}
	</div>
  @endif

	<div class="ui menu shadowless">		
		<a id="bulk-delete" @click="deleteItems" :href="route+ids.join()" class="item" :class="{disabled: isDisabled}">{{ __('Delete') }}</a>

		<a class="item export">{{ __('Export') }}</a>

		<div class="right menu">
			<form action="{{ route('prepaid_credits') }}" method="get" id="search" class="ui transparent icon input item">
        <input class="prompt" type="text" name="keywords" placeholder="{{ __('Search') }} ..." required>
        <i class="search link icon" onclick="$('#search').submit()"></i>
      </form>
			<a href="{{ route('prepaid_credits.create') }}" class="item ml-1">{{ __('Add') }}</a>
		</div>
	</div>
	
	<div class="table wrapper items prepaid_credits">
		<table class="ui unstackable celled basic table coupons">
			<thead>
				<tr>
					<th>
						<div class="ui fitted checkbox">
						  <input type="checkbox" @change="selectAll">
						  <label></label>
						</div>
					</th>
					<th class="five columns wide">{{ __('Name') }}</th>
					<th class="center aligned">{{ __('Amount') }}</th>
					<th class="center aligned">{{ __('Popular') }}</th>
					<th class="center aligned">{{ __('Updated at') }}</th>
					<th>{{ __('Actions') }}</th>
				</tr>
			</thead>
			<tbody>
				@foreach($prepaid_credits as $item)
				<tr data-id="{{ $item->id }}">
					<td class="center aligned">
						<div class="ui fitted checkbox select">
						  <input type="checkbox" value="{{ $item->id }}" @change="toogleId({{ $item->id }})">
						  <label></label>
						</div>
					</td>
					<td>{{ $item->name }}</td>
					<td class="center aligned">{{ price($item->amount, 0, 1, 2, 'code') }}</td>
					<td class="center aligned">
						<div class="ui toggle fitted checkbox disabled">
						  <input type="checkbox" {{ $item->popular ? "checked" : "" }}>
						  <label></label>
						</div>
					</td>
					<td class="center aligned">{{ $item->updated_at }}</td>
					<td class="center aligned">
						<div class="ui dropdown">
							<i class="bars icon mx-0"></i>
							<div class="menu dropdown left rounded-corner">
								<a href="{{ route('prepaid_credits.edit', $item->id) }}" class="item">{{ __('Edit') }}</a>
								<a @click="deleteItem($event)" href="{{ route('prepaid_credits.destroy', $item->id) }}" class="item">{{ __('Delete') }}</a>
							</div>
						</div>
					</td>
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>
	
	<form class="ui form modal export" action="{{ route('prepaid_credits.export') }}" method="POST">
		<div class="header">{{ __('Export :table_name table', ['table_name' => 'Prepaid_Credits']) }}</div>
		<div class="content">
			<input type="hidden" name="ids" :value="ids.join()">
			<input type="hidden" name="model" value="prepaid_credits">
			
			<table class="ui unstackable fluid basic table mt-0">
				<thead>
					<tr>
						<th>{{ __('Column') }}</th>
						<th>{{ __('Rename column') }}</th>
					</tr>
				</thead>
				<tbody>
					@foreach(\Schema::getColumnListing('prepaid_credits') as $column)
					<tr>
						<td>
							<div class="ui checked checkbox">
							  <input type="checkbox" id="{{ $column }}" name="columns[{{ $column }}][active]" checked="checked">
							  <label for="{{ $column }}">{{ $column }}</label>
							</div>
							
							<input type="hidden" name="columns[{{ $column }}][name]" value="{{ $column }}">
						</td>
						<td>
							<input type="text" name="columns[{{ $column }}][new_name]" placeholder="...">
						</td>
					</tr>
					@endforeach
				</tbody>				
			</table>
		</div>
		<div class="actions">
			<button class="ui yellow large circular button approve">{{ __('Export') }}</button>
			<button class="ui red circular large button cancel" type="button">{{ __('Cancel') }}</button>
		</div>
	</form>
</div>


<script>
	'use strict';

	$(() =>
	{
		$("#prepaid_credits .prepaid_credits tbody").sortable(
		{
			update: (event, ui) =>
			{
				let order = {};

				$("#prepaid_credits .prepaid_credits tbody tr").each(function(index)
				{
					order[$(this).data('id')] = index+1;
				})

				$.post('{{ route('prepaid_credits.sort') }}', {order: order})
				.done(data =>
				{

				})
			}
		});
	})

	var app = new Vue({
	  el: '#prepaid_credits',
	  data: {
	  	route: '{{ route('prepaid_credits.destroy', "") }}/',
	    ids: [],
	    isDisabled: true
	  },
	  methods: {
	  	toogleId: function(id)
	  	{
	  		if(this.ids.indexOf(id) >= 0)
	  			this.ids.splice(this.ids.indexOf(id), 1);
	  		else
	  			this.ids.push(id);
	  	},
	  	selectAll: function()
	  	{
	  		$('#prepaid_credits tbody .ui.checkbox.select').checkbox('toggle')
	  	},
	  	deleteItems: function(e)
	  	{
	  		var confirmationMsg = '{{ __('Are you sure you want to delete the selected items') }} ?';

	  		if(!this.ids.length || !confirm(confirmationMsg))
	  		{
	  			e.preventDefault();
	  			return false;
	  		}
	  	},
	  	deleteItem: function(e)
	  	{
	  		if(!confirm('{{ __('Are you sure you want to delete the selected items') }} ?'))
  			{
  				e.preventDefault();
  				return false;
  			}
	  	}
	  },
	  watch: {
	  	ids: function(val)
	  	{
	  		this.isDisabled = !val.length;
	  	}
	  }
	})
</script>
@endsection