@extends(view_path('master'))

@section('additional_head_tags')
<script type="application/javascript"> 
	'use strict';

	window.props['products'] = @json($products->reduce(function ($carry, $item) 
																	{
																	  $carry[$item->id] = $item;
																	  return $carry;
																	}, []));
</script>
@endsection

@section('body')
<div id="items">

		<div class="results">
			<div class="header">{{ __(':total results found.', ['total' => $products->total()]) }}</div>
			
			@if(array_intersect(array_keys(request()->query()), ['price_range', 'tags', 'sort']))
			<a href="{{ reset_filters() }}" class="item remove"><i class="close icon"></i>{{ __('Filter') }}</a>
			@endif
		</div>

		@if(!request()->filter)
		<div class="ui filter shadowless borderless menu">
			<a href="{{ filter_url('relevance_desc') }}" class="item @if(filter_is_selected('relevance_desc')) selected @endif">
				{{ __('Best match') }}
			</a>

			<a href="{{ filter_url(filter_is_selected('rating_asc') ? 'rating_desc' : 'rating_asc') }}" class="item {{ (filter_is_selected('rating_asc') || filter_is_selected('rating_desc')) ? 'selected' : '' }}">
				{{ __('Rating') }}
			</a>

			<a href="{{ filter_url(filter_is_selected('price_asc') ? 'price_desc' : 'price_asc') }}" class="item {{ (filter_is_selected('price_asc') || filter_is_selected('price_desc')) ? 'selected' : '' }}">
				{{ __('Price') }}
			</a>

			<a href="{{ filter_url('trending_desc') }}" class="item @if(filter_is_selected('trending_desc')) selected @endif">
				{{ __('Trending') }}
			</a>

			<a href="{{ filter_url(filter_is_selected('date_asc') ? 'date_desc' : 'date_asc') }}" class="item {{ (filter_is_selected('date_asc') || filter_is_selected('date_desc')) ? 'selected' : '' }}">
				{{ __('Release date') }}
			</a>
		</div>
		@endif

		<div class="ui five doubling cards products mt-1">
			@cards('axies-card', $products, "item")
		</div>
	
		@if($products->hasPages())
		<div class="mt-2"></div>
		{{ $products->appends(request()->query())->onEachSide(1)->links() }}
		{{ $products->appends(request()->query())->links('vendor.pagination.simple-semantic-ui') }}
		@endif

</div>
@endsection