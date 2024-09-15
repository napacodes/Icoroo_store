@extends('back.master')

@section('title', __('Create product license'))

@section('content')
<form class="ui large form" method="post" action="{{ route('licenses.store') }}">
	@csrf

	<div class="field">
		<button class="ui icon labeled large circular button" type="submit">
		  <i class="save outline icon"></i>
		  {{ __('Create') }}
		</button>
		<a class="ui icon labeled large circular button" href="{{ route('licenses') }}">
			<i class="times icon"></i>
			{{ __('Cancel') }}
		</a>
	</div>
	
	@if($errors->any())
      @foreach ($errors->all() as $error)
         <div class="ui negative fluid small message">
         	<i class="times icon close"></i>
         	{{ $error }}
         </div>
      @endforeach
	@endif

	<div class="ui fluid divider"></div>

	<div class="one column grid" id="license">
		<div class="column">
			<div class="field">
				<label>{{ __('Mark as regular') }}</label>
				<div class="ui dropdown selection floating fluid">
					<input type="hidden" name="regular" value="{{ old('regular', '0') }}">
					<div class="text">...</div>
					<div class="menu">
						<a class="item" data-value="1">{{ __('Yes') }}</a>
						<a class="item" data-value="0">{{ __('No') }}</a>
					</div>
				</div>
			</div>

			<div class="field">
				<label>{{ __('Name') }}</label>
				<input type="text" name="name" placeholder="..." value="{{ old('name') }}" autofocus required>
			</div>
		</div>
	</div>
</form>

@endsection