@php
$realtime_views_config = array_walk_alt(config('app.realtime_views', []), function(&$val, $key)
{
  if(in_array($key, ['website', 'product']))
  {
    unset($val['fake']);
  }

  return $val;
})
@endphp

'use strict';
      
window.props = {
  appName: '{{ config('app.name') }}',
  itemId: null,
  product: {},
  products: {},
  direction: '{{ locale_direction() }}',
  routes: {
    checkout: '{{ route('home.checkout') }}',
    products: '{{ route('home.products.category', '') }}',
    pages: '{{ route('home.page', '') }}',
    payment: '{{ route('home.checkout.payment') }}',
    coupon: '{{ route('home.checkout.validate_coupon') }}',
    notifRead: '{{ route('home.notifications.read') }}',
    addToCartAsyncRoute: '{{ route('home.add_to_cart_async') }}',
    subscriptionPayment: '{{ config('app.subscriptions.enabled') ? route('home.subscription.payment') : '' }}',
  },
  userMessage: '{{ session('user_message') }}',
  currentRouteName: '',
  location: window.location,
  transactionMsg: '{{ session('order_completed') }}',
  paymentProcessors:
  <?= json_encode(array_reduce(config('payments_gateways', []), function($carry, $gateway)
  {
    $carry[$gateway['name']] = true;
    return $carry;
  }, []), JSON_PRETTY_PRINT) ?>,
  paymentProcessor: '',
  paymentFees: @json(config('fees')),
  minimumPayments: @json(config('mimimum_payments')),
  currency: {
    code: '{{ config('payments.currency_code') }}', 
    symbol: '{{ config('payments.currency_symbol') }}', 
    position: '{{ config('payments.currency_position', 'left') }}'
  },
  activeScreenshot: null,
  subcategories: {!! collect(config('categories.category_children', []))->toJson() !!},
  categories: {!! collect(config('categories.category_parents', []))->toJson() !!},
  pages: {!! collect(config('pages', []))->where('deletable', 1)->toJson() !!},
  removeItemConfirmMsg: '{{ __('Are you sure you want to remove this item ?') }}',
  currencies: @json(config('payments.currencies') ?? [], JSON_UNESCAPED_UNICODE),
  currencyDecimals: {{ config('payments.currencies.'.currency('code').'.decimals') ?? 2 }},
  usersNotif: '{{ config('app.users_notif', '') }}',
  userNotifs: @json(config('notifications') ?? []),
  showPricesInKFormat: {{ var_export(config('payments.show_prices_in_k_format') ? true : false) }},
  allowAddToCart: {{ var_export(config('payments.enable_add_to_cart') ? true : false ) }},
  realtimeViews: '{!! base64_encode(json_encode(config('app.realtime_views', []))) !!}',
  recentPurchases: @json(config('app.fake_purchases', [])),
  exchangeRate: {{ config('payments.exchange_rate', 1) }},
  userCurrency: '{{ currency('symbol') }}',
}

window.isMasonry = '{{ config('app.masonry_layout') ? '1' : '0' }}' === '1';

@if(config('payments.update_pending_transactions') === 1)
fetch('{{ route('update_pending_transactions') }}');
@endif