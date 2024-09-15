@extends('back.master')

@section('title', __('User prepaid credits'))


@section('content')

<div class="row main" id="users-prepaid-credits">

	<div class="ui menu shadowless">		
		<a @click="deleteItems" :href="route+ids.join()" class="item" :class="{disabled: isDisabled}">{{ __('Delete') }}</a>

		<div class="right menu mr-1">
			<form action="{{ route('users_prepaid_credits') }}" method="get" id="search" class="ui transparent icon input item">
        <input class="prompt" type="text" name="keywords" placeholder="Search ..." required>
        <i class="search link icon" onclick="$('#search').submit()"></i>
      </form>
		</div>
	</div>

	@if(session('message'))
	<div class="ui fluid message">
			<i class="close icon"></i>
			{{ session('message') }}
	</div>
	@endif

	<div class="table wrapper items products">
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
						<a href="{{ route('users_prepaid_credits', ['orderby' => 'buyer', 'order' => $items_order]) }}">{{ __('Buyer') }}</a>
					</th>
					<th class="five columns wide">
						<a href="{{ route('users_prepaid_credits', ['orderby' => 'pack', 'order' => $items_order]) }}">{{ __('Pack') }}</a>
					</th>
					<th>
						<a href="{{ route('users_prepaid_credits', ['orderby' => 'amount', 'order' => $items_order]) }}">{{ __('Amount') }}</a>
					</th>
					<th>
						<a href="{{ route('users_prepaid_credits', ['orderby' => 'credits', 'order' => $items_order]) }}">{{ __('Credits') }}</a>
					</th>
					<th>
						<a href="{{ route('users_prepaid_credits', ['orderby' => 'status', 'order' => $items_order]) }}">{{ __('Status') }}</a>
					</th>
					<th>
						<a href="{{ route('users_prepaid_credits', ['orderby' => 'updated_at', 'order' => $items_order]) }}">{{ __('Updated at') }}</a>
					</th>
				</tr>
			</thead>

			<tbody>
				@foreach($users_prepaid_credits as $user_prepaid_credits)
				<tr>
					<td class="center aligned">
						<div class="ui fitted checkbox select">
						  <input type="checkbox" value="{{ $user_prepaid_credits->id }}" @change="toogleId({{ $user_prepaid_credits->id }})">
						  <label></label>
						</div>
					</td>
					<td class="center aligned">
						{{ $user_prepaid_credits->buyer }}
					</td>
					<td class="center aligned">
						{{ $user_prepaid_credits->pack }}
					</td>
					<td class="center aligned">
						{{ $user_prepaid_credits->amount }}
					</td>
					<td class="center aligned ui form credits two column wide">
						<input type="number" step="0.00001" class="rounded-corner" data-id="{{ $user_prepaid_credits->id }}" value="{{ $user_prepaid_credits->credits }}">
					</td>
					<td class="center aligned status">
					  @if($user_prepaid_credits->credits == 0)
					    <span class="ui basic rounded-corner fluid label red">{{ __('Spent') }}</span>
						@elseif($user_prepaid_credits->refunded)
							<span class="ui basic rounded-corner fluid label red">{{ __('Refunded') }}</span>
						@elseif($user_prepaid_credits->expired)
							<span class="ui basic rounded-corner fluid label red">{{ __('Expired') }}</span>
						@elseif($user_prepaid_credits->status === 'pending')
							<span class="ui basic rounded-corner fluid label red">{{ __('Pending') }}</span>
						@else
							<span class="ui basic rounded-corner fluid label teal">{{ __('Active') }}</span>
						@endif
					</td>
					<td class="center aligned two column wide">
						{{ $user_prepaid_credits->updated_at }}
					</td>
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>
	
	@if($users_prepaid_credits->hasPages())
	<div class="ui fluid divider"></div>

	{{ $users_prepaid_credits->appends($base_uri)->onEachSide(1)->links() }}
	{{ $users_prepaid_credits->appends($base_uri)->links('vendor.pagination.simple-semantic-ui') }}
	@endif
</div>

<script>
	'use strict';

	var app = new Vue({
	  el: '#users-prepaid-credits',
	  data: {
	  	route: '{{ route('users_prepaid_credits.destroy', "") }}/',
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
	  		$('#users-prepaid-credits tbody .ui.checkbox.select').checkbox('toggle')
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
	  },
	  watch: {
	  	ids: function(val)
	  	{
	  		this.isDisabled = !val.length;
	  	}
	  }
	})

	$(() =>
	{
		$('#users-prepaid-credits .form.credits input').on('keydown', function(e)
		{
			if(e.keyCode === 13)
			{
				let prepaidCreditsId = $(this).data('id');
				let newCredits = $(this).val().trim();

				if(!isNaN(newCredits) && newCredits >= 0)
				{
					$.post(`/admin/users_prepaid_credits/update/${prepaidCreditsId}`, {newCredits: newCredits})
					.done(data =>
					{
						if(!data.status)
							return alert(data.message);

						$(this).closest('td').siblings('td.status').html(data.message)
					})
				}
			}
		})
	})
</script>
@endsection