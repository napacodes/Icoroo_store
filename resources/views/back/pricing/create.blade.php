@extends('back.master')

@section('title', $title)

@section('additional_head_tags')

<link href="/assets/coloris/dist/coloris.min.css" rel="stylesheet">
<script src="/assets/coloris/dist/coloris.min.js"></script>

@endsection

@section('content')

<form class="ui large form" id="subscription" method="post" action="{{ route('pricing_table.store') }}">
	@csrf
	
	<div class="field">
		<button class="ui icon labeled large circular button" type="submit">
		  <i class="save outline icon"></i>
		  {{ __('Create') }}
		</button>
		<a class="ui icon labeled large circular button" href="{{ route('pricing_table') }}">
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

	<div class="two stackable fields">
		<div class="field">
			<label>{{ __('Name') }}</label>
			<input type="text" name="name" required autofocus value="{{ old('name') }}">
			<small>{{ __('e.g. : Plan 1, Basic, Ultimate, ... etc') }}.</small>
		</div>

		<div class="field">
			<label>{{ __('Color') }}</label>
			<input type="text" name="color" class="coloris" value="{{ old('color') }}">
		</div>	
	</div>

	<div class="field">
		<label>{{ __('Price') }}</label>
		<input type="number" step="0.01" name="price" value="{{ old('price') }}">
	</div>

	<div class="field">
		<label>{{ __('Mark as popular') }}</label>
		<div class="ui floating selection dropdown">
			<input type="hidden" name="popular" value="{{ old('popular', '0') }}">
			<div class="text">...</div>
			<div class="menu">
				<a class="item" data-value="1">{{ __('Yes') }}</a>
				<a class="item" data-value="0">{{ __('No') }}</a>
			</div>
		</div>
	</div>
	
	<div class="field">
		<label><span>{{ __('Specifications') }}</span> <button id="add-specs" class="ui button yellow circular icon ml-1-hf mr-0" type="button"><i class="plus icon mx-0"></i></button></label>
		<div class="items">
			@if(count(old('specifications.text', [])))
			@for($i=0; $i<count(old('specifications.text', [])); $i++)
			<div class="item">
				<div class="ui checkbox radio">
					<input type="checkbox" {{ old("specifications.included.${i}") ? 'checked' : '' }}>
					<input type="hidden" value="{{ old("specifications.included.${i}") ? '1' : '0' }}" name="specifications[included][]">
					<label></label>
				</div>		
				<input type="text" value="{{ old("specifications.text.${i}") }}" name="specifications[text][]">
			</div>
			@endfor
			@else
			<div class="item">
				<div class="ui checkbox radio">
					<input type="checkbox">
					<input type="hidden" value="0" name="specifications[included][]">
					<label></label>
				</div>		
				<input type="text" name="specifications[text][]">
			</div>
			<div class="item">
				<div class="ui checkbox radio">
					<input type="checkbox">
					<input type="hidden" value="0" name="specifications[included][]">
					<label></label>
				</div>		
				<input type="text" name="specifications[text][]">
			</div>
			<div class="item">
				<div class="ui checkbox radio">
					<input type="checkbox">
					<input type="hidden" value="0" name="specifications[included][]">
					<label></label>
				</div>		
				<input type="text" name="specifications[text][]">
			</div>
			<div class="item">
				<div class="ui checkbox radio">
					<input type="checkbox">
					<input type="hidden" value="0" name="specifications[included][]">
					<label></label>
				</div>		
				<input type="text" name="specifications[text][]">
			</div>
			@endif
		</div>
	</div>

	<fieldset>
		<legend>{{ __('Duration') }}</legend>

		<div class="two stackable fields">
			<div class="field">
				<label>{{ __('Title') }}</label>
				<input type="text" name="title" value="{{ old('title') }}" placeholder="{{ __('e.g. : Month, Day, 45 Days, Year, ... etc') }}">
			</div>

			<div class="field">
				<label>{{ __('Number of days') }} </label>
				<input type="number" name="days" value="{{ old('days') }}" placeholder="...">
			</div>
		</div>
	</fieldset>

	<fieldset>
		<legend>{{ __('Limit downloads') }}</legend>

		<div class="two stackable fields">
			<div class="field">
				<label>{{ __('Total downloads') }}</label>
				<input type="number" name="limit_downloads" value="{{ old('limit_downloads') }}">
			</div>

			<div class="field">
				<label>{{ __('Downloads per day') }}</label>
				<input type="number" name="limit_downloads_per_day" value="{{ old('limit_downloads_per_day') }}">
			</div>
		</div>

		<div class="field">
			<label>{{ __('Downloads of the same item during the subscription') }}</label>
	    	<input type="number" name="limit_downloads_same_item" value="{{ old('limit_downloads_same_item') }}">
		</div>
	</fieldset>

	<div class="field">
		<label>{{ __('Products') }}</label>
		<div class="ui search multiple floating selection dropdown">
			<input type="hidden" name="products" value="{{ old('products') }}">
			<div class="text">...</div>
			<div class="menu">
				@foreach(\App\Models\Product::where('active', 1)->get() as $product)
				<a class="item capitalize" data-value="{{ $product->id }}">{{ $product->name }}</a>
				@endforeach
			</div>
		</div>
		<small>{{ __('Products applicable for this plan. (Default: all)') }}</small>
	</div>

	<div class="field">
		<label>{{ __('Category of products') }}</label>
		<div class="ui search multiple floating selection dropdown">
			<input type="hidden" name="categories" value="{{ old('categories') }}">
			<div class="text">...</div>
			<div class="menu">
				@foreach(config('categories.category_parents') as $category)
				<a class="item capitalize" data-value="{{ $category->id }}">{{ $category->name }}</a>
				@endforeach
			</div>
		</div>
		<small>{{ __('Products applicable for this plan by category') }}</small>
	</div>

	<div class="field">
		<label>{{ __('Position') }}</label>
		<input type="number" name="position" value="{{ old('position') }}">
		<small>{{ __('Whether to show first, 2nd ... last.') }}.</small>
	</div>

</form>

<script type="application/javascript">
	'use strict'

	$(() => 
	{
		Coloris({
			el: '.coloris',
			themeMode: 'light', // light, dark, auto
			swatches: ['#264653','#2a9d8f','#e9c46a','#f4a261','#e76f51','#d62828','#023e8a','#0077b6','#0096c7','#00b4d8','#48cae4',]
		});

		$(document).on('change', '#subscription .items .item input[type="checkbox"]', function()
		{
			$(this).siblings('input[type="hidden"]').val($(this).prop('checked') ? '1' : '0');
		})

		$('#add-specs').on('click', function()
		{
			$('#subscription .items').append(`
				<div class="item">
					<div class="ui checkbox radio">
						<input type="checkbox">
						<input type="hidden" value="0" name="specifications[included][]">
						<label></label>
					</div>		
					<input type="text" name="specifications[text][]">
				</div>
			`);

			$('#subscription .items .item .ui.checkbox').checkbox();
		})

		$(document).on('keydown', 'input[name="specifications[text][]"]', function(e)
		{
			if(e.keyCode === 8 && !$(this).val().trim().length && $('#subscription .items .item').length > 4)
			{
				$(this).closest('.item').remove()
			}
		})
	})
</script>
@endsection