{{-- TENDRA --}}

@extends(view_path('master'))

@section('additional_head_tags')
<meta name="robots" value="noindex;nofollow">

@if(config('app.facebook_pixel'))
{!! config('app.facebook_pixel') !!}
@endif

<script type="application/javascript">
	'use strict';

	window.props['transactionMsg'] = 'order_completed';
</script>
@endsection

@section('body')
<div id="checkout-response" class="{{ $transaction->type }}">
	<div class="heading">
		<div class="title">{{ __('Order completed') }}</div>
		<div class="subheading">{{ __('Thank you for shopping with us') }}</div>
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
					<div class="order-id">
						{{ __('Order number') }} : <span>{{ $transaction->reference_id }}</span>
					</div>
					<div class="contact">
						{!! __('If you have questions about your order, please email us at <span>:admin_email</span>', ['admin_email' => config('app.email')]) !!}
					</div>
				</div>
			</div>
			<div class="buttons @guest guest @endguest">
				<a href="/" class="homepage">{{ __('Homepage') }}</a>
				@auth
				@if($transaction->type == "product")
				<a href="{{ route('home.purchases') }}" class="purchases">{{ __('My purchases') }}</a>
				@elseif($transaction->type === "subscription")
				<a href="{{ route('home.user_subscriptions') }}" class="purchases">{{ __('My subscriptions') }}</a>
				@else
				<a href="{{ route('home.user_prepaid_credits') }}" class="purchases">{{ __('My credits') }}</a>
				@endif
				@endauth
			</div>
		</div>

		@if($transaction->type === "product")
		<div class="column right">
			<div class="heading">{{ __('Downloads') }}</div>
			<div class="items">
				@foreach($items as $item)
				<div class="item">
					<div class="name">{{ $item->name }}</div>
					@if($item->files > 1)
					<div class="text list">
						<div class="download">{{ __('Download') }}</div>
						<div class="menu">
							@if($item->file)
							<a href="{{ $item->file }}" target="_blank" class="link">{{ __('Main file') }}</a>
							@endif
							@if($item->license)
							<a href="{{ $item->license }}" target="_blank" class="link">{{ __('License') }}</a>
							@endif
							@if($item->key)
							<a href="{{ $item->key }}" target="_blank" class="link">{{ __('Key') }}</a>
							@endif
						</div>
					</div>
					@else
					<div class="text">
						<a href="{{ $item->file ?? $item->key ?? $item->license }}" class="download" target="_blank">{{ __('Download') }}</a>
					</div>
					@endif
				</div>
				@endforeach
			</div>
			<div class="note">
				{!! __('Download links have been sent to your email address, please check your <span>:buyer_email</span> inbox', ['buyer_email' => $transaction->user_email ?? $transaction->guest_email]) !!}.
			</div>
		</div>
		@endif
	</div>
</div>
@endsection