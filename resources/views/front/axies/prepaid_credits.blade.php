@extends(view_path('master'))

@section('additional_head_tags')
<meta name="robots" content="noindex, nofollow">
@foreach(config('payments_gateways', []) as $name => $gateway)
	@if(config("payment_gateways.{$name}.assets"))
	@foreach(config("payment_gateways.{$name}.assets", []) as $asset)
		@if($asset["type"] == "js")
			<script type="text/javascript" charset="utf-8" src="{{ $asset["src"] }}" {{ $asset["defer"] ? "defer" : "" }}></script>
		@elseif($asset["type"] == "css")
			<link rel="stylesheet" href="{{ $asset["src"] }}">
		@elseif($asset["type"] == "js_init")
			<script type="text/javascript" charset="utf-8" {{ $asset["defer"] ? "defer" : "" }}>
				"use strict";
				@if($asset["replace"] ?? null)
					{!! str_replace_adv($asset["code"], $asset["replace"]) !!}
				@else
					{!! $asset["code"] !!}
				@endif
			</script>
		@endif
	@endforeach
	@endif
@endforeach
@endsection

@section('body')
	<div v-if="transactionMsg === 'processing'">
		<div class="ui active dimmer">
	    <div class="ui small text loader">{{ __('Processing') }}</div>
	  </div>
	</div>

	<div id="prepaid-credits">
		<div class="header">
			<div class="title capitalize">{{ __('Add prepaid credits') }}</div>
		</div>

		<div class="body">
			<div class="left-side">
				<div class="header">
					<div class="title capitalize">{{ __('Add prepaid credits') }}</div>
				</div>
				
				<div class="packs">
					<div class="ui three stackable doubling cards">
						@foreach($packs as $pack)
						<div class="fluid card {{ $pack->popular ? 'popular' : '' }}" :class="{active: prepaidCreditsPackId == '{{ $pack->id }}'}">
							<div class="content header">
								<div class="name">{{ $pack->name }}</div>
								<div class="price">{!! price($pack->amount, 0, 1, 2, 'code', 0, null, 1) !!}</div>

								@if($pack->popular)
								<div class="tag">{{ __('Popular') }}</div>
								@endif
							</div>
							@if($pack->specs)
							<div class="content body">
								{!! base64_decode($pack->specs) !!}
							</div>
							@endif
							<div class="content footer">
								<button class="ui basic large button mx-0" @click="setPrepaidCreditsPackId('{{ $pack->id }}')">{{ __('Select') }}</button>
							</div>
						</div>
						@endforeach
					</div>
				</div>
			</div>

			<div class="right-side">
				<div class="methods">
					<div class="header">
						{{ __('Payment method') }}
					</div>

					<div class="ui fluid dropdown divided selection mx-0" :class="{disabled: prepaidCreditsPackId == null}">
						<div class="text capitalize">...</div>
						<div class="menu">
							@foreach(config('payments_gateways') as $name => $gateway)
							@if($name !== "n-a" && $name !== 'credits')
							<a class="item" title="{{ $gateway['description'] ?? '' }}" @click="setPaymentProcessor('{{ $name }}')" data-text='<img src="{{ $gateway['icon'] }}">{{ $name }}'>
								<img src="{{ $gateway['icon'] ?? '' }}">
								<div class="content">
									<div class="name">{{ __($name) }}</div>
									<div class="description">
										{{ shorten_str($gateway['description'] ?? '', 60) }}
									</div>
								</div>
							</a>
							@endif
							@endforeach
						</div>
					</div>
				</div>

				<div class="checkout">
					<form action="{{ route('home.checkout.payment') }}" method="post" id="form-checkout" class="ui big form">
		        <div class="form-fields two fields"></div>
		        <input type="hidden" placeholder="..." class="d-none" name="coupon">
					</form>

					<button class="ui button large mx-0 fluid" :class="{disabled: prepaidCreditsPackId == null}" type="button" @click="checkout($event)">
						{{ __('Checkout') }}
					</button>
				</div>
			</div>
		</div>
	</div>
@endsection