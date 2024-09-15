@extends(view_path('master'))


@section('body')
<div id="pricing">	

	@if($subscriptions->count())
	<div class="title">
		<div class="header">{{ __('The right plan for your business') }}</div>
		<div class="subheader">
			{{ __('Chose plan that works best for you and your team') }}
		</div>
	</div>

	<div class="ui three stackable doubling cards">
		@foreach($subscriptions as $subscription)
		<div class="card {{ $subscription->popular ? 'popular' : '' }}">
			<div class="content header">
				<div class="label">
					<div>{{ __('Popular') }}</div>
				</div>
				<img class="icon" src="/assets/images/pricing-2.webp">
				<div class="description">
					<div class="title">{{ __($subscription->name) }}</div>
					<div class="pricing">
						<div class="price">{{ price($subscription->price) }}</div>
						<div class="time">{{ __($subscription->title) }}</div>
					</div>
				</div>
			</div>
			<div class="content body">
				@if(strip_tags($subscription->description))
				{!! $subscription->description !!}
				@elseif($subscription->specifications)
					@foreach($subscription->specifications ?? [] as $specification)
					<div class="item">
						<span class="icon {{ $specification->included ? 'included' : '' }}"><img src="/assets/images/checkbox-2.webp"></span>
						<div class="text">{{ $specification->text }}</div>
					</div>
					@endforeach
				@endif
			</div>
			<div class="content footer">
				<a href="{{ pricing_plan_url($subscription) }}" class="ui button fluid mx-0">{{ __('Chose plan') }}</a>
			</div>
		</div>
		@endforeach
	</div>
	@endif

</div>
@endsection