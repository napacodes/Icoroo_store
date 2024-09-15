{{-- TENDRA --}}

@extends(view_path('master'))

@section('additional_head_tags')
<script type="application/javascript" src="{{ asset_("assets/FileSaver.2.0.4.min.js") }}"></script>

<script type="application/javascript">
	'use strict';
	window.props['itemId'] = null;
	window.props['keycodes'] = @json($keycodes ?? []);
</script>
@endsection

@section('body')
<div class="ui shadowless celled one column grid my-0" id="user">
	@if(!route_is('home.credits'))
	<div class="column title rounded-corner">
		<div class="ui secondary menu fluid">
			<a href="{{ route('home.profile') }}" class="header item {{ route_is('home.profile') ? 'active' : '' }}">{{ __('Profile') }}</a>
			<a href="{{ route('home.notifications') }}" class="header item {{ route_is('home.notifications') ? 'active' : '' }}">{{ __('Notifications') }}</a>
			<a href="{{ route('home.favorites') }}" class="header item {{ route_is('home.favorites') ? 'active' : '' }}">{{ __('Collection') }}</a>
			<a href="{{ route('home.purchases') }}" class="header item {{ route_is('home.purchases') ? 'active' : '' }}">{{ __('Purchases') }}</a>
			@if(config('app.prepaid_credits.enabled'))
			<a href="{{ route('home.user_prepaid_credits') }}" class="header item {{ route_is('home.user_prepaid_credits') ? 'active' : '' }}">{{ __('Prepaid credits') }}</a>
			@endif
			@if(config('app.subscriptions.enabled'))
			<a href="{{ route('home.user_subscriptions') }}" class="header item {{ route_is('home.user_subscriptions') ? 'active' : '' }}">{{ __('Subscriptions') }}</a>
			@endif
			<a href="{{ route('home.invoices') }}" class="header item {{ route_is('home.invoices') ? 'active' : '' }}">{{ __('Invoices') }}</a>
		</div>
	</div>
	@endif
	
	@if($errors->any())
  @foreach ($errors->all() as $error)
     <div class="ui negative fluid circular-corner bold w-100 large message">
     	<i class="times icon close"></i>
     	{{ $error }}
     </div>
  @endforeach
	@endif

	@if(route_is('home.purchases'))

		<div class="column items purchases px-0 mt-2">
			<div class="table-wrapper">
				<table class="ui fluid celled borderless unstackable table">
					<thead>
						<tr>
							<th class="order-id">{{ __('Order ID') }}</th>
							<th class="items">{{ __('Items') }}</th>
							<th class="status">{{ __('Status') }}</th>
							<th class="date">{{ __('Date') }}</th>
							<th class="links">{{ __('Links') }}</th>
						</tr>
					</thead>
					<tbody>
						@foreach($transactions as $transaction)
						<tr class="parent">
							<td class="order-id">{{ $transaction->reference_id }}</td>
							<td class="items">{{ count($transaction->items) }}</td>
							<td class="status"><div>{{ __(ucfirst($transaction->status)) }}</div></td>
							<td class="date">{{ $transaction->updated_at->format('Y-m-d H:i:s') }}</td>
							<td class="links">
								@if(count($transaction->items))
								<a class="toggler"><img src="/assets/images/dropdown.webp"></a>
								@else
								-
								@endif
							</td>
						</tr>
						@if(count($transaction->items))
						<tr class="products">
							<td colspan="4" class="p-0">
								<table class="ui fluid basic borderless unstackable table">
									<tbody>
										@foreach($transaction->items as $item)
										<tr>
											<td class="name"><a href="{{ $item->url }}">{{ $item->name }}</a></td>
											<td class="downloads two columns wide">
												@if($item->files > 1)
												<div class="ui dropdown button nothing hoverable fluid">
													<div class="text">{{ __('Download') }}</div>
													<div class="menu">
														@if($item->file)
														<a href="{{ $item->file }}" target="_blank" class="item">{{ __('Main file') }}</a>
														@endif
														@if($item->license)
														<a href="{{ $item->license }}" target="_blank" class="item">{{ __('License') }}</a>
														@endif
														@if($item->key)
														<a href="{{ $item->key }}" target="_blank" class="item">{{ __('Key') }}</a>
														@endif
													</div>
												</div>
												@else
												<a href="{{ $item->file ?? $item->license ?? $item->key }}" class="ui button fluid">{{ __('Download') }}</a>
												@endif
											</td>
										</tr>
										@endforeach
									</tbody>	
								</table>
							</td>
						</tr>
						@endif
						@endforeach
					</tbody>
					<tfoot>
						<tr>
							<th colspan="5">
								&nbsp;
								@if($transactions->hasPages())
								{{ $transactions->appends(request()->query())->onEachSide(1)->links() }}
								{{ $transactions->appends(request()->query())->links('vendor.pagination.simple-semantic-ui') }}
								@endif
							</th>
						</tr>	
					</tfoot>		
				</table>	
			</div>
		</div>

	@elseif(route_is('home.favorites'))
	
		<div class="column items favorites px-0 mt-1">
			<div class="ui four stackable doubling cards" v-if="Object.keys(favorites).length" v-cloak>
				<div class="card fluid" v-for="product in favorites">
					<div class="content header">
						<a :title="product.name" :href="'item/' + product.id + '/' + product.slug">
							<img :src="'/storage/covers/' + product.cover">
						</a>
					</div>
					<div class="content body">
						<a :title="product.name" :href="'item/' + product.id + '/' + product.slug">@{{ product.name }}</a>
					</div>
					<div class="content footer">
						<a class="ui pink circular button" :href="'/items/category/' + product.category_slug">@{{ product.category_name }}</a>
					</div>
				</div>
			</div>
		</div>

	@elseif(route_is('home.user_subscriptions') && config('app.subscriptions.enabled'))
	
		<div class="column items subscriptions px-0 mt-2">
			@if($user_subscriptions->count())

			<div class="wrapper">
				<div class="item titles">
					<div class="name">{{ __('Name') }}</div>
					<div class="date">{{ __('Starts at') }}</div>
					<div class="date">{{ __('Expires at') }}</div>
					<div class="days">{{ __('Remaining days') }}</div>
					<div class="downloads">{{ __('Downloads') }}</div>
					<div class="downloads">{{ __('Daily Downloads') }}</div>
					<div class="status">{{ __('Status') }}</div>
				</div>

				@foreach($user_subscriptions as $user_subscription)
				<div class="item">
					<div class="name">{{ $user_subscription->name }}</div>
					<div class="date">{{ format_date($user_subscription->starts_at, 'jS M Y \a\\t H:i') }}</div>
					<div class="date">
						@if($user_subscription->ends_at)
						{{ format_date($user_subscription->ends_at, 'jS M Y \a\\t H:i') }}
						@else
						{{ __('Unlimited') }}
						@endif
					</div>
					<div class="days">
						@if($user_subscription->ends_at)
						{{ $user_subscription->remaining_days }}
						@else
						{{ __('Unlimited') }}
						@endif
					</div>
					<div class="downloads">
						@if($user_subscription->limit_downloads > 0)
						{{ "{$user_subscription->downloads}/{$user_subscription->limit_downloads}" }}
						@else
						{{ __('Unlimited') }}
						@endif
					</div>
					<div class="downloads">
						@if($user_subscription->limit_downloads_per_day > 0)
						{{ "{$user_subscription->daily_downloads}/{$user_subscription->limit_downloads_per_day}" }}
						@else
						{{ __('Unlimited') }}
						@endif
					</div>
					<div class="status">
						@if($user_subscription->expired)
						<div class="ui red basic circular large label">{{ __('Expired') }}</div>
						@elseif(!$user_subscription->payment_status)
						<div class="ui orange basic circular large label">{{ __('Pending') }}</div>
						@else
						<div class="ui teal basic circular large label">{{ __('Active') }}</div>
						@endif
					</div>
				</div>
				@endforeach
			</div>
			@endif
		</div>

	@elseif(route_is('home.notifications'))
	
		<div class="column items notifications mt-1">
	    @if($notifications->count())
	    
	    <div class="items">
	      @foreach($notifications as $notification)
	      <a class="item mx-0 @if(!$notification->read) unread @endif"
	          data-id="{{ $notification->id }}"
	          data-href="{{ route('home.product', ['id' => $notification->product_id, 'slug' => $notification->slug . ($notification->for == 1 ? '#support' : ($notification->for == 2 ? '#reviews' : ''))]) }}">

	        <div class="image" style="background-image: url({{ asset_("storage/".($notification->for == 0 ? 'covers' : 'avatars')."/{$notification->image}") }})"></div>

	        <div class="content pl-1">
	          <p>{!! __($notification->text, ['product_name' => "<strong>{$notification->name}</strong>"]) !!}</p>
	          <time>{{ \Carbon\Carbon::parse($notification->updated_at)->diffForHumans() }}</time>
	        </div>
	      </a>
	      @endforeach
	    </div>

		    @if($notifications->hasPages())
		    <div class="ui divider"></div>

		    {{ $notifications->onEachSide(1)->links() }}
			  {{ $notifications->links('vendor.pagination.simple-semantic-ui') }}
		    @endif
	    @endif
		</div>

	@elseif(route_is('home.profile'))
	
		<div class="column items profile p-1 mt-1">
			<form class="ui large form w-100" action="{{ route('home.profile') }}" enctype="multipart/form-data" method="post">
				@csrf

				<div class="field avatar">
					<div class="ui unstackable items">
						<div class="item">
							<div class="content">
								<div class="ui circular image">
									<img src="{{ asset_("storage/avatars/".($user->avatar ?? 'default.webp').'?v='.time()) }}">
								</div>

								<button class="ui yellow circular button mx-0" type="button" 
												onclick="$('#user .profile input[name=\'avatar\']').click()">{{ __('Upload') }}</button>
								<input type="file" name="avatar" class="d-none">
							</div>

							<div class="content">
								<div class="name">{{ $user->name ?? null }}</div>
								<div class="country">{{ $user->country ?? null}}</div>
								<div class="member-since">{{ format_date($user->created_at, 'd F Y') }}</div>
								<div class="email">
									{{ $user->email }}
									@if(config('app.email_verification'))
									@if($user->email_verified_at)
									<sup class="verified">({{ __('Verified') }})</sup>
									@else
									<sup>({{ __('Unverified') }} - <a @click="sendEmailVerificationLink('{{ $user->email }}')">{{ __('Send verification link') }}</a>)</sup>
									@endif
									@endif
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="two fields">
					<div class="field">
						<label>{{ __('First name') }}</label>
						<input type="text" name="firstname" value="{{ old('firstname', $user->firstname ?? null) }}">
					</div>
					<div class="field">
						<label>{{ __('Last name') }}</label>
						<input type="text" name="lastname" value="{{ old('lastname', $user->lastname ?? null) }}">
					</div>
				</div>

				<div class="two fields">
					<div class="field">
						<label>{{ __('Username') }}</label>
						<input type="text" name="name" value="{{ old('name', $user->name ?? null) }}" required>
					</div>
					<div class="field">
						<label>{{ __('Affiliate name') }}</label>
						<input type="text" name="affiliate_name" value="{{ old('affiliate_name', $user->affiliate_name ?? null) }}">
					</div>
				</div>

				<div class="two fields">
					<div class="field">
						<label>{{ __('Country') }}</label>
						<input type="text" name="country" value="{{ old('country', $user->country ?? null) }}">
					</div>
					<div class="field">
						<label>{{ __('City') }}</label>
						<input type="text" name="city" value="{{ old('city', $user->city ?? null) }}">
					</div>
				</div>

				<div class="two fields">
					<div class="field">
						<label>{{ __('Address') }}</label>
						<input type="text" name="address" value="{{ old('address', $user->address ?? null) }}">
					</div>
					<div class="field">
						<label>{{ __('Zip code') }}</label>
						<input type="text" name="zip_code" value="{{ old('zip_code', $user->zip_code ?? null) }}">
					</div>
				</div>

				<div class="two fields">
					<div class="field">
						<label>{{ __('ID number') }}</label>
						<input type="text" name="id_number" value="{{ old('id_number', $user->id_number ?? null) }}">
					</div>
					<div class="field">
						<label>{{ __('Phone') }}</label>
						<input type="text" name="phone" value="{{ old('phone', $user->phone ?? null) }}">
					</div>	
				</div>
				
				<div class="field">
					<label>{{ __('State') }}</label>
					<input type="text" name="state" value="{{ old('state', $user->state ?? null) }}">
				</div>

				@if(config('affiliate.enabled') && mb_strlen($user->affiliate_name))
				<div class="field">
					<label>{{ __('Earnings cashout method') }}</label>
					<div class="ui floating selection fluid dropdown">
						<input type="hidden" value="{{ old('cashout_method', $user->cashout_method) }}" name="cashout_method">
						<div class="text"></div>
						<div class="menu">
							@if(config('affiliate.cashout_methods.paypal_account'))
							<a class="item" data-value="paypal_account">{{ __('Paypal account') }}</a>
							@endif

							@if(config('affiliate.cashout_methods.bank_account'))
							<a class="item" data-value="bank_account">{{ __('Bank Transfer') }}</a>
							@endif
						</div>
					</div>
				</div>

				<div class="option paypal_account {{ $user->cashout_method != 'paypal_account' ? 'd-none' : '' }} mb-1">
					<div class="field">
						<label>{{ __('PayPal Email Address') }}</label>
						<input type="text" name="paypal_account" value="{{ old('paypal_account', $user->paypal_account ?? null) }}">
					</div>
				</div>

				<div class="option bank_account {{ $user->cashout_method != 'bank_account' ? 'd-none' : '' }} mb-1">
					<div class="field">
						<label>{{ __('Bank address') }}</label>
						<input type="text" name="bank_account[bank_address]" value="{{ old('bank_account.bank_address', $user->bank_account->bank_address ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Account holder name') }}</label>
						<input type="text" name="bank_account[holder_name]" value="{{ old('bank_account.holder_name', $user->bank_account->holder_name ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Account holder address') }}</label>
						<input type="text" name="bank_account[holder_address]" value="{{ old('bank_account.holder_address', $user->bank_account->holder_address ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Account number') }}</label>
						<input type="text" name="bank_account[account_number]" value="{{ old('bank_account.account_number', $user->bank_account->account_number ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('IBAN Code') }}</label>
						<input type="text" name="bank_account[iban]" value="{{ old('bank_account.iban', $user->bank_account->iban ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('SWIFT Code') }}</label>
						<input type="text" name="bank_account[swift]" value="{{ old('bank_account.swift', $user->bank_account->swift ?? null) }}">
					</div>
				</div>
				@endif

				<div class="field">
					<label>{{ __('Receive notifications via email') }}</label>
					<div class="ui floating selection fluid dropdown">
						<input type="hidden" value="{{ old('receive_notifs', $user->receive_notifs ?? '1') }}" name="receive_notifs">
						<div class="text"></div>
						<div class="menu">
							<a class="item" data-value="1">{{ __('Yes') }}</a>
							<a class="item" data-value="0">{{ __('No') }}</a>
						</div>
					</div>
				</div>

				<div class="field">
					<label>{{ __('Enable Two Factor Authentication') }}</label>
					<div class="ui floating selection fluid dropdown">
						<input type="hidden" value="{{ old('two_factor_auth', $user->two_factor_auth ?? '0') }}" name="two_factor_auth">
						<div class="text"></div>
						<div class="menu">
							<a class="item" data-value="1">{{ __('Yes') }}</a>
							<a class="item" data-value="0">{{ __('No') }}</a>
						</div>
					</div>
				</div>
				
				<div class="ui fluid yellow shadowless segment">
					<h4 class="ui red header">{{ __('Change password') }}</h4>

					<div class="two fields mb-0">
						<div class="field">
							<label>{{ __('Old password') }}</label>
							<input type="text" name="old_password" value="{{ old('old_password') }}">
						</div>
						<div class="field">
							<label>{{ __('New password') }}</label>
							<input type="text" name="new_password" value="{{ old('old_password') }}">
						</div>
					</div>	
				</div>
				
				<div class="ui fluid divider"></div>

				<div class="field">
					<button class="ui blue circular button" type="submit">{{ __('Save changes') }}</button>
				</div>
			</form>
		</div>

	@elseif(route_is('home.invoices'))

		<div class="column items invoices mt-2">
			@if($invoices)

			<div class="table wrapper">
				<table class="ui basic large borderless unstackable table">
					<thead>
						<tr>
							<th>{{ __('Reference') }}</th>
							<th>{{ __('Date') }}</th>
							<th>{{ __('Amount') }}</th>
							<th>{{ __('Export PDF') }}</th>
						</tr>
					</thead>
					<tbody>
						@foreach($invoices ?? [] as $invoice)
						<tr>
							<td>{{ $invoice->reference_id }}</td>
							<td>{{ $invoice->created_at }}</td>
							<td>{{ $invoice->currency .' '. $invoice->amount }}</td>
							@if(config('app.invoice.template', 1) == 1)
							<td><button class="ui large basic circular button" type="button" @click="downloadItem({{ $invoice->id }}, '#download-invoice')">{{ __('Export') }}</button></td>
							@else
							<td><button class="ui large basic circular button" type="button" @click="downloadInvoice({{ $invoice->id }})">{{ __('Export') }}</button></td>
							@endif
						</tr>
						@endforeach
					</tbody>
				</table>
			</div>

			@if($invoices->hasPages())
			<div class="ui hidden divider"></div>
			{{ $invoices->appends(request()->query())->onEachSide(1)->links() }}
			{{ $invoices->appends(request()->query())->links('vendor.pagination.simple-semantic-ui') }}
			@endif

			<form action="{{ route('home.export_invoice') }}" class="d-none" method="post" id="download-invoice">
				@csrf
				<input type="hidden" name="itemId" v-model="itemId">
			</form>
			@endif
		</div>
	@elseif(route_is('home.user_prepaid_credits') && config('app.prepaid_credits.enabled'))
	
		<div class="items prepaid_credits invoices">
			@if($user_prepaid_credits->count())
			<div class="table-wrapper p-2">
				<table class="ui basic large unstackable table">
					<thead>
						<tr>
							<th>{{ __('Name') }}</th>
							<th class="center aligned">{{ __('Amount') }}</th>
							<th class="center aligned">{{ __('Credits') }}</th>
							<th class="center aligned">{{ __('Expires at') }}</th>
							<th class="center aligned">{{ __('Discount') }}</th>
							<th class="center aligned">{{ __('Status') }}</th>
						</tr>
					</thead>

					<tbody>
						@foreach($user_prepaid_credits as $prepaid_credit)
						<tr>
							<td>{{ __($prepaid_credit->name) }}</td>
							<td class="center aligned">{{ price($prepaid_credit->amount, 0, 1, 2, 'code', 0, null) }}</td>
							<td class="center aligned">{{ price($prepaid_credit->credits, 0, 1, 2, 'code', 0, null) }}</td>
							<td class="center aligned">{{ $prepaid_credit->expires_at ?? '-' }}</td>
							<td class="center aligned">{{ $prepaid_credit->discount ? "{$prepaid_credit->discount}%" : '-' }}</td>
							<td class="center aligned">
								@if($prepaid_credit->expired)
								<div class="ui basic large red disabled label rounded-corner">{{ __('Expired') }}</div>
								@else
									@if($prepaid_credit->credits == 0)
									<div class="ui basic large red label rounded-corner">{{ __('Spent') }}</div>
									@elseif($prepaid_credit->status == 'paid')
									<div class="ui basic large teal label rounded-corner">{{ __('Active') }}</div>
									@else
									<div class="ui basic large yellow label rounded-corner">{{ __('Pending') }}</div>
									@endif
								@endif
							</td>
						</tr>
						@endforeach
					</tbody>

					<tfoot>
						<tr>
							<th colspan="6">
								{{ $user_prepaid_credits->onEachSide(1)->links() }}
								{{ $user_prepaid_credits->links('vendor.pagination.simple-semantic-ui') }}
							</th>
						</tr>
					</tfoot>
				</table>
			</div>
			@endif
		</div>

	@elseif(route_is('home.user_coupons'))

		<div class="items coupons">
			<div class="header extra">
				<div class="title">{{ __('Coupons') }}</div>
			</div>

			<div class="items-list">
				<div class="titles">
					<div class="code">{{ __('Code') }}</div>
					<div class="value">{{ __('Value') }}</div>
					<div class="for">{{ __('Applicable For') }}</div>
					<div class="once">{{ __('Usable once') }}</div>
					<div class="starts_at">{{ __('Starts at') }}</div>
					<div class="expires_at">{{ __('Expires at') }}</div>
				</div>
				@foreach($coupons ?? [] as $coupon)
				<div class="content {{ $coupon->expires_at <= date('Y-m-d H:i:s') ? 'expired' : '' }}">
					<div class="code">{{ $coupon->code }}</div>
					<div class="value">{{ $coupon->is_percentage ? ("%{$coupon->value} ".__('OFF')) : price($coupon->value, false) }}</div>
					<div class="for">{{ $coupon->products_ids ? __('Products') : ($coupon->subscriptions_ids ? __('Subscriptions') : __('All')) }}</div>
					<div class="once">{{ $coupon->once ? __('Yes') : __('No') }}</div>
					<div class="starts_at">{{ $coupon->starts_at }}</div>
					<div class="expires_at">{{ $coupon->expires_at }}</div>
				</div>
				@endforeach
				<div class="content mt-1">
					@if($coupons->hasPages())
					{{ $coupons->onEachSide(1)->links() }}
					{{ $coupons->links('vendor.pagination.simple-semantic-ui') }}
					@endif
				</div>
			</div>
		</div>

	@elseif(route_is('home.credits') && config('app.prepaid_credits.enabled'))
	<div class="items credits">
		<div class="header extra">
			<div class="title">{{ __('Earnings & Credits') }}</div>
		</div>

		<div class="body">
			<div class="header ui four stackable doubling cards">
				<div class="fluid card">
					<div class="content">
						<div class="image">
							<img src="{{ asset_("assets/images/purchased-items.png") }}">
						</div>
						<div class="content">
							<div class="name">{{ __('Purchased items') }}</div>
							<div class="count">{{ $purchased_items }}</div>
						</div>
					</div>
				</div>

				<div class="fluid card">
					<div class="content">
						<div class="image">
							<img src="{{ asset_("assets/images/prepaid-credits.png") }}">
						</div>
						<div class="content">
							<div class="name">{{ __('Prepaid credits') }}</div>
							<div class="count">{{ price($prepaid_credits, 0) }}</div>
						</div>
					</div>
				</div>

				<div class="fluid card">
					<div class="content">
						<div class="image">
							<img src="{{ asset_("assets/images/affiliate-earnings.png") }}">
						</div>
						<div class="content">
							<div class="name">{{ __('Affiliate earnings') }}</div>
							<div class="count">{{ price($affiliate_credits, 0) }}</div>
						</div>
					</div>
				</div>

				<div class="fluid card">
					<div class="content">
						<div class="image">
							<img src="{{ asset_("assets/images/orders-2.png") }}">
						</div>
						<div class="content">
							<div class="name">{{ __('Completed orders') }}</div>
							<div class="count">{{ $completed_orders }}</div>
						</div>
					</div>
				</div>

				<div class="fluid card">
					<div class="content">
						<div class="image">
							<img src="{{ asset_("assets/images/referred_users.png") }}">
						</div>
						<div class="content">
							<div class="name">{{ __('Referred users') }}</div>
							<div class="count">{{ $referred_users }}</div>
						</div>
					</div>
				</div>

				<div class="fluid card">
					<div class="content">
						<div class="image">
							<img src="{{ asset_("assets/images/cashout.png") }}">
						</div>
						<div class="content">
							<div class="name">{{ __('Cashed out earnings') }}</div>
							<div class="count">{{ price($cashed_out_earnings, 0) }}</div>
						</div>
					</div>
				</div>

				<div class="fluid card">
					<div class="content">
						<div class="image">
							<img src="{{ asset_("assets/images/spent-credits.png") }}">
						</div>
						<div class="content">
							<div class="name">{{ __('Spent credits') }}</div>
							<div class="count">{{ price($spent_credits, 0) }}</div>
						</div>
					</div>
				</div>

				<div class="fluid card">
					<div class="content">
						<div class="image">
							<img src="{{ asset_("assets/images/available-credits.png") }}">
						</div>
						<div class="content">
							<div class="name">{{ __('Available credits') }}</div>
							<div class="count">{{ price(user_credits(), 0) }}</div>
						</div>
					</div>
				</div>
			</div>

			<div class="ui fluid card affiliate-earnings">
				<div class="content header">
					<img src="{{ asset_("assets/images/rising.png") }}">
					<div class="title">
						<div>{{ __('Affiliate earnings') }}</div>
						<div>{{ __('Monthly affiliate earnings evolution') }}</div>
					</div>
					<div class="date ui form">
						<input type="text" name="affiliate_earnings_date" value="{{ date('m/d/Y') }}" placeholder="{{ __('Date') }}">
					</div>
				</div>
				<div class="content body grid">
					<div class="wrapper">
						@foreach($earnings_steps as $step)
				      <div class="row">
				      	<div>{{ ceil($step) }}</div>
				      	@for($k = 0; $k <= (count($earnings_per_day) - 1); $k++)
				      	<div>
				      		@if($step == 0)
						      <span data-variation="flowing" data-tooltip="{{ price($earnings_per_day[$k] ?? '0', 0) }}" style="height: {{ $earnings_per_day[$k] > 0 ? ($earnings_per_day[$k] / $max_value * 305) : '1' }}px"><i class="circle blue icon mx-0"></i></span>
						      @endif
						    </div>
				      	@endfor
				      </div>
						 @endforeach

						<div class="row">
							<div>-</div>
							@for($day = 1; $day <= count($earnings_per_day); $day++)
							<div>{{ $day }}</div>
							@endfor
						</div>
					</div>
				</div>
			</div>

			<div class="ui fluid card orders">
				<div class="content header">
					<img src="{{ asset_("assets/images/orders-3.png") }}">
					<div class="title">
						<div>{{ __('Completed orders') }}</div>
						<div>{{ __('Orders completed with your affiliate link') }}</div>
					</div>
				</div>
				<div class="content body">
					<div class="table-wrapper">
						<table class="ui large unstackable celled table">
							<thead>
								<tr>
									<th>{{ __('Reference') }}</th>
									<th>{{ __('Referee name') }}</th>
									<th>{{ __('Order amount') }}</th>
									<th>{{ __('Earnings') }}</th>
									<th>{{ __('Purchased items') }}</th>
									<th>{{ __('Order status') }}</th>
									<th>{{ __('Date') }}</th>
								</tr>
							</thead>
							<tbody>
								@foreach($orders as $order)
								<tr>
									<td>{{ $order->reference_id }}</td>
									<td>{{ $order->referee_name }}</td>
									<td>{{ price($order->amount, 0) }}</td>
									<td>{{ price($order->earnings, 0) }}</td>
									<td class="items">
										@if($order->type == 'subscription')
											<div class="item"><i class="circle outline icon"></i>{{ __('Subscription - :name', ['name' => $order->items]) }}</div>
										@else
											@foreach(explode(',', $order->items) as $item)
											<div class="item" title="{{ $item }}"><i class="circle outline icon"></i>{{ shorten_str($item, 50) }}</div>
											@endforeach
										@endif
									</td>
									<td>
										@if($order->refunded)
										<div class="ui label basic rounded-corner red">{{ __('Reversed') }}</div>
										@elseif($order->confirmed == '0' || $order->status == 'pending')
										<div class="ui label basic rounded-corner orange">{{ __('Pending') }}</div>
										@elseif($order->status == 'paid')
										<div class="ui label basic rounded-corner teal">{{ __('Confirmed') }}</div>
										@endif
									</td>
									<td>{{ $order->updated_at }}</td>
								</tr>
								@endforeach
							</tbody>
							@if($orders->hasPages())
							<tfoot>
								<tr>
									<th colspan="7">
										{{ $orders->appends(request()->query())->onEachSide(1)->links() }}
										{{ $orders->appends(request()->query())->links('vendor.pagination.simple-semantic-ui') }}
									</th>
								</tr>
							</tfoot>		
							@endif
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
	@endif
</div>

@endsection