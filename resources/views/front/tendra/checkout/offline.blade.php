@extends(view_path('master'))

@section('additional_head_tags')
<meta name="robots" content="nofollow, noindex">
@endsection


@section('body')
<div id="checkout-response" class="offline">
	<div class="heading">
		<div class="title">{{ __('Order created') }}</div>
		<div class="subheading">{{ __('Please confirm your order') }}</div>
	</div>

	<div class="columns">
		<div class="column left">
			<div class="heading">{{ __('Order summary') }}</div>
			<div class="content">
				<div class="items">
					@foreach($transaction->details->items ?? [] as $key => $item)
					<div class="item {{ in_array($key, ['tax', 'fee']) || is_numeric($key) ? 'plus' : "minus"}}">
						<div class="name">{{ __($item->name) }}</div>
						<div class="text">{{ price($item->value, 0) }}</div>
					</div>
					@endforeach
					<div class="item total">
						<div class="name">{{ __('Total due amount') }}</div>
						<div class="text">{{ price($transaction->details->custom_amount ?? $transaction->details->total_amount, 0) }}</div>
					</div>
				</div>
				<div class="note">
					<div class="contact">
						{!! bbcode_to_html(config('payments_gateways.offlinepayment.instructions'), nl2br:true) !!}
					</div>
				</div>
			</div>
			<div class="buttons @guest guest @endguest">
				<a class="homepage" href="/">{{ __('Cancel') }}</a>
				<a class="purchases" href="{{ route('home.checkout.order_completed', ['order_id' => $transaction->reference_id, 'processor' => 'offlinepayment']) }}">{{ __('Confirm') }}</button>
			</div>
		</div>
	</div>
</div>
@endsection