@extends(view_path('master'))

@section('additional_head_tags')
<meta name="robots" content="noindex, nofollow">

<script type="application/javascript">
	window.props['subscriptionId'] = {{ $subscription->id }};
	window.props['subscriptionPrice'] = '{{ $subscription->price }}';
</script>

@foreach(config('payments_gateways', []) as $name => $gateway)
	@if(config("payment_gateways.{$name}.assets"))
	@foreach(config("payment_gateways.{$name}.assets", []) as $asset)
		@if($asset["type"] == "js")
			<script type="text/javascript" charset="utf-8" 
							@if($asset["replace"] ?? null)
							src="{{ str_replace_adv($asset["src"], $asset["replace"]) }}" 
							@else
							src="{{ $asset["src"] }}"
							@endif
			{{ $asset["defer"] ? "defer" : "" }}></script>
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
	<div class="ui active processing-transaction dimmer">
    <div class="ui small text loader">{{ __('Processing') }}</div>
  </div>
</div>

<div id="checkout-page">
	<div class="container">
		<div class="left-side">
			<div class="header">
				<div>{{ __('Membership') }}</div>
				<div class="subheader">
					{{ __(':name Plan', ['name' => $subscription->name]) }}
				</div>
			</div>
			
			<div class="items">
				<div class="item subscription">
					<div class="header">
						<div class="name">
							{{ $subscription->name }}
						</div>
						<div class="price">
							<span>@{{ price(totalAmount, true, true) }}</sup></span>
						</div>
					</div>
					<div class="description">
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
				</div>
			</div>
		</div>

		<div class="right-side">
			<div class="summary" v-if="cartItems > 0">
				<div class="header">
					{{ __('Order summary') }}
				</div>
				<div class="content">
					<div class="fee">
						{{ __('Purchase Fee : ') }}
						<span v-if="!isNaN(getPaymentFee())">
							@{{ price(getPaymentFee()) }}
						</span>
					</div>

					<div class="discount">
						{{ __('Discount : ') }}
						<span v-if="!isNaN(couponValue)">
							@{{ price(Number(couponValue).toFixed(2)) }}
						</span>
					</div>

					<div class="total">
						{{ __('Total : ') }}
						<span v-if="!isNaN(getTotalAmount())">
							@{{ price(getTotalAmount()) }}
						</span>
					</div>
				</div>
			</div>


			<div class="methods" :class="{'d-none': !Number(getTotalAmount())}">
				<div class="header">
					{{ __('Payment method') }}
				</div>

				<div class="ui fluid dropdown divided selection mx-0">
					<div class="text capitalize">...</div>
					<div class="menu">
						@foreach(config('payments_gateways') as $name => $gateway)
						@if($name == 'credits' && (!config('app.prepaid_credits.enabled') && !config('affiliate.enabled')))
							@continue
						@endif

						@if($name != "n-a")
						<a class="item" title="{{ $gateway['description'] ?? '' }}" @click="setPaymentProcessor('{{ $name }}')" data-text='<img src="{{ $gateway['icon'] }}">{{ __($name) }}'>
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

			<div class="coupon">
				<div class="header">
					{{ __('Coupon code') }}
				</div>

				<div class="ui action fluid input">
					<input type="text" placeholder="..." name="coupon">
					<button class="ui button" v-if="!couponRes.status" type="button" @click="applyCoupon($event)">{{ __('Apply') }}</button>
					<button class="ui button" v-else type="button" @click="removeCoupon($event)">{{ __('Clear') }}</button>
				</div>

				<div class="message" :class="{negative: !couponRes.status, positive: couponRes.status}" v-if="couponRes.msg !== undefined">
					<i class="close link icon"></i>
					@{{ couponRes.msg }}
				</div>
			</div>

			<div class="checkout">
				<form action="{{ route('home.checkout.payment') }}" method="post" id="form-checkout" class="ui big form">
	        <div class="form-fields two fields"></div>
				</form>

				@foreach(config('payments_gateways', []) as $name => $gateway)
					@if(config("payment_gateways.{$name}.form.checkout_buttons"))
						@foreach(config("payment_gateways.{$name}.form.checkout_buttons", []) as $checkout_button)
							@if($checkout_button["replace"] ?? null)
							{!! str_replace_adv($checkout_button["html"], $checkout_button["replace"]) !!}
							@else
							{!! $checkout_button["html"] !!}
							@endif
						@endforeach
					@endif
				@endforeach

				<button class="btn waving fluid" type="button" @click="checkout($event)" :class="{'d-none': (getTotalAmount() > 0 && !paymentProcessor.length)}">
					<span class="text">{{ __('Checkout') }}</span>
					<span class="liquid"></span>
				</button>
			</div>
		</div>
	</div>
</div>

@if(config('app.prepaid_credits.enabled') || config('affiliate.enabled'))
<form class="ui small modal" v-if="Object.keys(creditsOrder).length" id="credits-checkout-form" :action="'/credits_checkout/'+creditsOrder.transaction_id" method="post">
	<div class="header">
		{{ __('Order confirmation') }}
	</div>

	<div class="content p-0">
		<table class="ui large table unstackable">
			<thead>
				<tr>
					<th>{{ __('Name') }}</th>
					<th>{{ __('Value') }}</th>
				</tr>
			</thead>
			<tbody>
				<tr v-for="(item, index) in creditsOrder.items" :class="index">
					<td>@{{ __(item.name) }}</td>
					<td>@{{ price(item.value) }}</td>
				</tr>
			</tbody>
			<tfoot>
				<tr>
					<th>@{{ __('Total amount') }}</th>
					<th>@{{ price(creditsOrder.total_amount) }}</th>
				</tr>
			</tfoot>
		</table>
	</div>

	<div class="actions">
		<a href="{{ route('home') }}" class="ui white large rounded-corner button">{{ __('Cancel') }}</a>
		<button type="submit" class="ui yellow large rounded-corner mr-0 button">{{ __('Confirm') }}</button>
	</div>
</form>
@endif

@endsection