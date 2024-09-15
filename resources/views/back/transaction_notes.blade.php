@extends('back.master')

@section('title', __('Transaction notes'))


@section('content')

@if(session('response'))
<div class="ui fluid small positive bold message">
	<i class="close icon"></i>
	{{ session('response') }}
</div>
@endif

<div class="row main" id="transaction-notes">

	<div class="ui menu shadowless">
		<a id="bulk-delete" @click="deleteItems" :href="route+ids.join()" class="item" :class="{disabled: isDisabled}">{{ __('Delete') }}</a>

		<div class="right menu">
			<form action="{{ route('transaction_notes.index') }}" method="get" id="search" class="ui transparent icon input item mr-1">
        <input class="prompt" type="text" name="keywords" placeholder="{{ __('Search') }} ..." required>
        <i class="search link icon" onclick="$('#search').submit()"></i>
      </form>
		</div>
	</div>
	
	<div class="table wrapper items transactions">
		<table class="ui unstackable celled basic table">
			<thead>
				<tr>
					<th>
						<div class="ui fitted checkbox">
						  <input type="checkbox" @change="selectAll">
						  <label></label>
						</div>
					</th>
					<th>{{ __('Reference ID') }}</th>
					<th>
						<a href="{{ route('transaction_notes.index', ['orderby' => 'email', 'order' => $items_order]) }}">{{ __('Buyer') }}</a>
					</th>
					<th>
						<a href="{{ route('transaction_notes.index', ['orderby' => 'created_at', 'order' => $items_order]) }}">{{ __('Created at') }}</a>
					</th>
					<th>
						<a href="{{ route('transaction_notes.index', ['orderby' => 'updated_at', 'order' => $items_order]) }}">{{ __('Updated at') }}</a>
					</th>
					<th>{{ __('Content') }}</th>
					<th>{{ __('Reply') }}</th>
				</tr>
			</thead>
			<tbody>
				@foreach($notes as $note)
				<tr id="note-{{ $note->id }}">
					<td class="center aligned">
						<div class="ui fitted checkbox select">
						  <input type="checkbox" value="{{ $note->id }}" @change="toogleId({{ $note->id }})">
						  <label></label>
						</div>
					</td>
					<td class="center aligned">{{ $note->reference_id }}</td>
					<td class="left aligned">{{ $note->email }}</td>
					<td class="center aligned">{{ $note->created_at }}</td>
					<td class="center aligned updated-at">{{ $note->updated_at }}</td>
					<td class="center aligned">
						<a class="ui button basic rounded" @click="showNotes('{{ $note->id }}')">{{ __('Read') }}</a>
					</td>
					<td class="center aligned">
						<a class="ui button teal basic rounded" @click="showReplyForm({{ json_encode($note) }})">{{ __('Reply') }}</a>
					</td>
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>
	
	@if($notes->hasPages())
	<div class="ui fluid divider"></div>
	{{ $notes->appends($base_uri)->onEachSide(1)->links() }}
	{{ $notes->appends($base_uri)->links('vendor.pagination.simple-semantic-ui') }}
	@endif

	<div class="ui modal notes">
		<div class="header">{{ __('Notes') }}</div>
		<div class="content"></div>
	</div>

	<div class="ui modal form reply">
		<div class="header">{{ __('Reply to') }} @{{ reply.name }}</div>
		<div class="content">
			<input type="hidden" v-model="reply.noteId">
			<div class="field">
				<textarea v-model="reply.message" placeholder="{{ __('Enter your message') }}" cols="30" rows="10"></textarea>
			</div>
		</div>
		<div class="actions">
			<button class="ui button blue rounded" @click="sendReply">{{ __('Send') }}</button>
		</div>
	</div>
</div>

<script>
	'use strict';
	
	var app = new Vue({
	  el: '#transaction-notes',
	  data: {
	  	route: '{{ route('transaction_notes.destroy', "") }}/',
	    ids: [],
	    isDisabled: true,
	    transaction_id: null,
	    reply: {
	    	name: '',
				refId: '',
				email: '',
	    	noteId: '',
	    	message: ''
	    }
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
	  		$('#transaction-notes tbody .ui.checkbox.select').checkbox('toggle')
	  	},
	  	showNotes: function(id)
	  	{
	  		$('.ui.modal.notes .content').html('')

	  		$.post(`/admin/transaction_notes/show/${id}`)
	  		.done(data => 
	  		{
	  			$('.ui.modal.notes .content').html(data.response).closest('.notes').modal({
	  				centered: true,
	  				closable: true,
	  			})
	  			.modal('show')
	  		})
	  	},
	  	showReplyForm: function(note)
	  	{
	  		this.reply = {
	  			name: `${note.reference_id} - ${note.email}`,
	  			refId: note.reference_id,
	  			email: note.email,
	  			noteId: note.id,
	  			message: '',
	  		};

	  		Vue.nextTick(() =>
	  		{
	  			$('.ui.modal.reply').modal('show')
	  		})
	  	},
	  	sendReply: function(e)
	  	{
	  		$(e.target).toggleClass('loading disabled', true);

	  		$.post(`/admin/transaction_notes`, this.reply)
	  		.done(data =>
	  		{
	  			if(data.hasOwnProperty('updated_at'))
	  			{
	  				$(`tr#note-${this.reply.noteId} .updated-at`).text(data.updated_at)
	  			}
	  		})
	  		.always(() =>
	  		{
	  			this.reply = {
			    	name: '',
						refId: '',
						email: '',
			    	noteId: '',
			    	message: ''
			    };

			    $(e.target).toggleClass('loading disabled', false);
			    $('.ui.modal.reply').modal('hide')
	  		})
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
	  	},
	  }
	})

	$('.ui.modal.export').modal({closable: false})
</script>
@endsection