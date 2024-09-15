@extends('back.master')

@section('title', __('Custom routes'))


@section('content')

<div class="row main" id="custom-routes">

	<div class="ui menu shadowless">		
		<a id="bulk-delete" @click="deleteItems" :href="route+ids.join()" class="item" :class="{disabled: isDisabled}">{{ __('Delete') }}</a>

		<a class="item export">{{ __('Export') }}</a>

		<div class="right menu">
			<form action="{{ route('custom_routes') }}" method="get" id="search" class="ui transparent icon input item">
        <input class="prompt" type="text" name="keywords" placeholder="{{ __('Search') }} ..." required>
        <i class="search link icon" onclick="$('#search').submit()"></i>
      </form>
			<a href="{{ route('custom_routes.create') }}" class="item ml-1">{{ __('Add') }}</a>
		</div>
	</div>
	
	<div class="table wrapper items pages">
		<table class="ui unstackable celled basic table">
			<thead>
				<tr>
					<th>
						<div class="ui fitted checkbox">
						  <input type="checkbox" @change="selectAll">
						  <label></label>
						</div>
					</th>
					<th class="five columns wide">
						<a href="{{ route('custom_routes', ['orderby' => 'name', 'order' => $items_order]) }}">{{ __('Name') }}</a>
					</th>
					<th>
						<a href="{{ route('custom_routes', ['orderby' => 'views', 'order' => $items_order]) }}">{{ __('Views') }}</a>
					</th>
					<th>
						<a href="{{ route('custom_routes', ['orderby' => 'method', 'order' => $items_order]) }}">{{ __('Method') }}</a>
					</th>
					<th>
						<a href="{{ route('custom_routes', ['orderby' => 'active', 'order' => $items_order]) }}">{{ __('Active') }}</a>
					</th>
					<th>
						<a href="{{ route('custom_routes', ['orderby' => 'csrf', 'order' => $items_order]) }}">{{ __('CSRF') }}</a>
					</th>
					<th>
						<a href="{{ route('custom_routes', ['orderby' => 'updated_at', 'order' => $items_order]) }}">{{ __('Updated at') }}</a>
					</th>
					<th>{{ __('Actions') }}</th>
				</tr>
			</thead>
			<tbody>
				@foreach($custom_routes as $custom_route)
				<tr>
					<td class="center aligned">
						<div class="ui fitted checkbox select">
						  <input type="checkbox" value="{{ $custom_route->id }}" @change="toogleId({{ $custom_route->id }})">
						  <label></label>
						</div>
					</td>
					<td><a href="/{{ $custom_route->slug }}">{{ ucfirst($custom_route->name) }}</a></td>
					<td class="center aligned">{{ $custom_route->views }}</td>
					<td class="center aligned">{{ mb_strtoupper($custom_route->method) }}</td>
					<td class="center aligned">
						<div class="ui toggle fitted checkbox">
						  <input type="checkbox" name="active" @if($custom_route->active) checked @endif data-id="{{ $custom_route->id }}" data-status="active" @change="updateStatus($event)">
						  <label></label>
						</div>
					</td>
					<td class="center aligned">
						<div class="ui toggle fitted checkbox">
						  <input type="checkbox" name="csrf_protection" @if($custom_route->csrf_protection) checked @endif data-id="{{ $custom_route->id }}" data-status="csrf_protection" @change="updateStatus($event)">
						  <label></label>
						</div>
					</td>
					<td class="center aligned">{{ $custom_route->updated_at }}</td>
					<td class="center aligned one column wide">
						<div class="ui dropdown">
							<i class="bars icon mx-0"></i>
							<div class="menu dropdown left rounded-corner">
								<a href="{{ route('custom_routes.edit', $custom_route->id) }}" class="item">{{ __('Edit') }}</a>
								<a @click="deleteItem($event)" href="{{ route('custom_routes.destroy', $custom_route->id) }}" class="item">{{ __('Delete') }}</a>
							</div>
						</div>
					</td>
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>
	
	@if($custom_routes->hasPages())
	<div class="ui fluid divider"></div>

	{{ $custom_routes->appends($base_uri)->onEachSide(1)->links() }}
	{{ $custom_routes->appends($base_uri)->links('vendor.pagination.simple-semantic-ui') }}
	@endif
</div>

<script>
	'use strict';

	var app = new Vue({
	  el: '#custom-routes',
	  data: {
	  	route: '{{ route('custom_routes.destroy', "") }}/',
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
	  		$('#custom-routes tbody .ui.checkbox.select').checkbox('toggle')
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
	  	},
	  	updateStatus: function(e)
	  	{	
	  		let thisEl  = $(e.target);
	  		let id 			= thisEl.data('id');
	  		let status  = thisEl.data('status');

	  		$.post('{{ route('custom_routes.status') }}', {id: id, status: status})
				.done(function(res)
				{
					if(res.success)
					{
						thisEl.checkbox('toggle');
					}
				}, 'json')
				.fail(function()
				{
					alert('Failed')
				})
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