@extends('back.master')

@section('title', __('Database settings'))

@section('additional_head_tags')
<link rel=stylesheet href=/assets/pretty-print-json.css>
<script src=/assets/pretty-print-json.min.js></script>
@endsection

@section('content')

<form class="ui large main form" method="post" spellcheck="false" action="{{ route('settings.update', 'database') }}">

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

	<div class="one column grid">
		<div class="ui fluid card">
			<div class="content">

				<div class="field">
					<label>{{ __('Host') }}</label>
					<input type="text" name="database[host]" value="{{ old('database.host', $settings->database->host ?? env('DB_HOST') ?? '127.0.0.1') }}">
				</div>
				
				<div class="field">
					<label>{{ __('Database') }}</label>
					<input type="text" name="database[database]" value="{{ old('database.database', $settings->database->database ?? env('DB_DATABASE') ?? null) }}">
				</div>

				<div class="field">
					<label>{{ __('Username') }}</label>
					<input type="text" name="database[username]" value="{{ old('database.username', $settings->database->username ?? env('DB_USERNAME') ?? null) }}">
				</div>

				<div class="field">
					<label>{{ __('Password') }}</label>
					<input type="text" name="database[password]" value="{{ old('database.password', $settings->database->password ?? env('DB_PASSWORD') ?? null) }}">
				</div>

				<div class="field">
					<label>{{ __('Charset') }}</label>
					<input type="text" name="database[charset]" value="{{ old('database.charset', $settings->database->charset ?? env('DB_CHARSET') ?? null) }}">
				</div>
			
				<div class="field">
					<label>{{ __('Collation') }}</label>
					<input type="text" name="database[collation]" value="{{ old('database.collation', $settings->database->collation ?? env('DB_COLLATION') ?? null) }}">
				</div>
				
				<div class="field">
					<label>{{ __('Port') }}</label>
					<input type="text" name="database[port]" value="{{ old('database.port', $settings->database->port ?? env('DB_PORT') ?? '3306') }}">
				</div>

				<div class="field">
					<label>{{ __('Sort buffer size') }} <sup>{{ __('In MB') }}</sup></label>
					<input type="number" name="database[sort_buffer_size]" step="0.01" value="{{ old('database.sort_buffer_size', $settings->database->sort_buffer_size ?? env('DB_SORT_BUFFER_SIZE') ?? 2) }}">
				</div>
				<div class="field">
					<label>{{ __('SQL mode') }}</label>
						<div class="ui dropdown multiple search floating selection">
						<input type="hidden" name="database[sql_mode]" value="{{ old('database.sql_mode', $settings->database->sql_mode ?? env('DB_SQL_MODE') ?? 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION') }}">
						<div class="default text">...</div>
						<div class="menu">
              <div class="item" data-value="ONLY_FULL_GROUP_BY">ONLY_FULL_GROUP_BY</div>
              <div class="item" data-value="STRICT_TRANS_TABLES">STRICT_TRANS_TABLES</div>
              <div class="item" data-value="NO_ZERO_IN_DATE">NO_ZERO_IN_DATE</div>
              <div class="item" data-value="NO_ZERO_DATE">NO_ZERO_DATE</div>
              <div class="item" data-value="ERROR_FOR_DIVISION_BY_ZERO">ERROR_FOR_DIVISION_BY_ZERO</div>
              <div class="item" data-value="NO_ENGINE_SUBSTITUTION">NO_ENGINE_SUBSTITUTION</div>
              <div class="item" data-value="ALLOW_INVALID_DATES">ALLOW_INVALID_DATES</div>
              <div class="item" data-value="IGNORE_SPACE">IGNORE_SPACE</div>
              <div class="item" data-value="NO_AUTO_VALUE_ON_ZERO">NO_AUTO_VALUE_ON_ZERO</div>
              <div class="item" data-value="NO_BACKSLASH_ESCAPES">NO_BACKSLASH_ESCAPES</div>
              <div class="item" data-value="PAD_CHAR_TO_FULL_LENGTH">PAD_CHAR_TO_FULL_LENGTH</div>
              <div class="item" data-value="REAL_AS_FLOAT">REAL_AS_FLOAT</div>
              <div class="item" data-value="STRICT_ALL_TABLES">STRICT_ALL_TABLES</div>
						</div>
					</div>
				</div>


				<div class="field">
					<label>{{ __('Time zone') }}</label>
					<input type="text" name="database[timezone]" value="{{ old('database.timezone', $settings->database->timezone ?? env('DB_TIMEZONE', '+00:00')) }}">
				</div>

				<div class="field">
					<button class="ui large circular yellow button" type="button" id="check-connection">{{ __('Check connection') }}</button>
				</div>
			</div>
		</div>

		<div class="ui fluid card">
			<div class="content header">
				<h3>{{ __('Execute custom database query') }}</h3>
			</div>
			<div class="content body">
				<textarea name="db_query" rows="5" placeholder="SELECT * FROM users LIMIT 5"></textarea>
			</div>
			<div class="content footer">
				<button class="ui rounded teal large button" type="button" id="exec-db-query">{{ __('Execute') }}</button>
				<div class="ui button large dropdown rounded selection">
					<input type="hidden" name="db_action" value="Select">
					<div class="text">{{ __('Type') }}</div>
					<div class="menu">
						<div class="item" data-value="Select">{{ __('Select') }}</div>
						<div class="item" data-value="Update">{{ __('Update') }}</div>
						<div class="item" data-value="Insert">{{ __('Insert') }}</div>
						<div class="item" data-value="Delete">{{ __('Delete') }}</div>
						<div class="item" data-value="Statement">{{ __('Statement') }}</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div id="db-query-response">
		<i class="close circular inverted yellow icon"></i>
		<div class="content"></div>
	</div>

</form>

<script>
	'use strict';

	$(function()
	{
		$('#exec-db-query').on('click', function()
		{
			let query = $('textarea[name="db_query"]').val().trim();
			let action  = $('input[name="db_action"]').val().trim();

			if(query.length && /^(Select|Update|Insert|Delete|Statement)$/i.test(action))
			{
				let options = {
					indent: 4,
					lineNumbers: true,
					linkUrls: true,
					linksNewTab: true,
					quoteKeys: true
				};

				$.post('{{ route('execute_db_query') }}', {query, action})
				.done(data =>
				{
					data = typeof data.response == "boolean" ? data : data.response;

					$('#db-query-response').show().find('.content').html(prettyPrintJson.toHtml(data, options));
				})
			}
		});


		$('#db-query-response .close').on('click', function()
		{
			$('#db-query-response').hide().find('.content').html('');
		})


		$('#check-connection').on('click', function()
		{
			var formData = $('form.main').serialize();

			$.post('{{ route('settings.check_database_connection') }}', formData, 'json')
			.done(function(data)
			{
				alert(data.status)
			})
		})
	})
</script>

@endsection