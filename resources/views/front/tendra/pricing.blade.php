@extends(view_path('master'))


@section('body')
	
	<div class="ui one column shadowless celled grid my-0 px-1" id="pricing">	

		@if($subscriptions->count())
		<div class="row">
			<div class="column title">
				<h1>{{ __('Our Pricing Plans') }}</h1>
				<h3>{{ __('Explore our pricing plans, from :first to :last, choose the one that meets your needs.', ['first' => $subscriptions->first()->name, 'last' => $subscriptions->last()->name]) }}</h3>
			</div>

			<div class="column mx-auto px-0">
				<div class="ui three doubling cards">
					@foreach($subscriptions as $subscription)
					<div class="card">
						<div class="contents">
							<div class="content price">
								<div style="color: {{ $subscription->color ?? '#000' }}">
									{{ price($subscription->price, false, true) }}
									@if($subscription->title)<span>/ {{ __($subscription->title) }}</span>@endif
								</div>
							</div>
							
							<div class="content description">
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

							<div class="content buy">
								@if($active_subscription && !config('app.subscriptions.accumulative'))
								<a class="ui large circular button disabled mx-0" style="background: {{ $subscription->color ?? '#667694' }}">
									{{ __('Get started') }}
								</a>
								@else
								<a href="{{ pricing_plan_url($subscription) }}" class="ui large circular button mx-0" style="background: {{ $subscription->color ?? '#667694' }}">
									{{ __('Get started') }}
								</a>
								@endif
							</div>

							<div class="name" style="background: {{ $subscription->color ?? '#667694' }}">
								<span>{{ __($subscription->name) }}</span>
							</div>
						</div>
					</div>
					@endforeach
				</div>

				@if($active_subscription && !config('app.subscriptions.accumulative'))
				<small class="mt-2 d-block">* {{ __("It's not possible to subscribe to another membership plan while your previous one has not expired yet.") }}</small>
				@endif
			</div>
		</div>
		@endif

	</div>

@endsection