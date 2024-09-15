@extends('back.master')

@section('title', __('Dashboard'))

@section('additional_head_tags')

@endsection

@section('content')

<div class="row main" id="dashboard">

	<div class="ui four doubling cards general">

		<div class="card fluid items">
			<div class="content top">
				<h3 class="header">{{ __('Items') }}</h3>
			</div>
			<div class="content bottom px-0">
				<div class="l-side">
					<div class="ui image">
						<img loading="lazy" src="{{ asset_('assets/images/items.png') }}">
					</div>
				</div>
				<div class="r-side">
					<h3>{{ $counts->products }}</h3>
				</div>
			</div>
		</div>

		<div class="card fluid orders">
			<div class="content top">
				<h3 class="header">{{ __('Orders') }}</h3>
			</div>
			<div class="content bottom px-0">
				<div class="l-side">
					<div class="ui image">
						<img loading="lazy" src="{{ asset_('assets/images/cart.png') }}">
					</div>
				</div>
				<div class="r-side">
					<h3>{{ $counts->orders }}</h3>
				</div>
			</div>
		</div>

		<div class="card fluid earnings">
			<div class="content top">
				<h3 class="header">{{ __('Earnings') }}</h3>
			</div>
			<div class="content bottom px-0">
				<div class="l-side">
					<div class="ui image">
						<img loading="lazy" src="{{ asset_('assets/images/dollar.png') }}">
					</div>
				</div>
				<div class="r-side">
					<h3>{{ config('payments.currency_code') .' '. number_format($counts->earnings, 2) }}</h3>
				</div>
			</div>
		</div>

		<div class="card fluid users">
			<div class="content top">
				<h3 class="header">{{ __('Users') }}</h3>
			</div>
			<div class="content bottom px-0">
				<div class="l-side">
					<div class="ui image">
						<img loading="lazy" src="{{ asset_('assets/images/users.png') }}">
					</div>
				</div>
				<div class="r-side">
					<h3>{{ $counts->users }}</h3>
				</div>
			</div>
		</div>

		<div class="card fluid comments">
			<div class="content top">
				<h3 class="header">{{ __('Comments') }}</h3>
			</div>
			<div class="content bottom px-0">
				<div class="l-side">
					<div class="ui image">
						<img loading="lazy" src="{{ asset_('assets/images/comments.png') }}">
					</div>
				</div>
				<div class="r-side">
					<h3>{{ $counts->comments }}</h3>
				</div>
			</div>
		</div>

		<div class="card fluid subscribers">
			<div class="content top">
				<h3 class="header">{{ __('Subscribers') }}</h3>
			</div>
			<div class="content bottom px-0">
				<div class="l-side">
					<div class="ui image">
						<img loading="lazy" src="{{ asset_('assets/images/subscribers.png') }}">
					</div>
				</div>
				<div class="r-side">
					<h3>{{$counts->newsletter_subscribers  }}</h3>
				</div>
			</div>
		</div>

		<div class="card fluid categories">
			<div class="content top">
				<h3 class="header">{{ __('Categories') }}</h3>
			</div>
			<div class="content bottom px-0">
				<div class="l-side">
					<div class="ui image">
						<img loading="lazy" src="{{ asset_('assets/images/tag.png') }}">
					</div>
				</div>
				<div class="r-side">
					<h3>{{ $counts->categories }}</h3>
				</div>
			</div>
		</div>


		<div class="card fluid posts">
			<div class="content top">
				<h3 class="header">{{ __('Posts') }}</h3>
			</div>
			<div class="content bottom px-0">
				<div class="l-side">
					<div class="ui image">
						<img loading="lazy" src="{{ asset_('assets/images/pages.png') }}">
					</div>
				</div>
				<div class="r-side">
					<h3>{{ $counts->posts }}</h3>
				</div>
			</div>
		</div>
	</div>



	<div class="transactions-sales-wrapper">
		<div class="latest transactions">
			<table class="ui celled unstackable table">
				<thead>
					<tr>
						<th class="left aligned w-auto"><h3>{{ __('Latest transactions') }}</h3></th>
						<th class="left aligned">{{ __('Buyer') }}</th>
						<th class="left aligned">{{ __('Amount') }} ({{ config('payments.currency_code') }})</th>
						<th class="left aligned">{{ __('Processor') }}</th>
						<th class="left aligned">{{ __('Date') }}</th>
					</tr>
				</thead>
				<tbody>
					@foreach($transactions as $transaction)
					<tr>
						<td>
							@if($transaction->is_subscription)
								{{ __('Subscription') .' - '. $transaction->products[0] }}
							@else
								@foreach($transaction->products as $product)
									<div>{{ $product }}</div>
								@endforeach
							@endif
						</td>
						<td class="left aligned">{{ $transaction->buyer_name ?? $transaction->buyer_email }}</td>
						<td class="left aligned">{{ number_format($transaction->amount, 2) }}</td>
						<td class="left aligned capitalize">{{ $transaction->processor }}</td>
						<td class="left aligned">{{ $transaction->date }}</td>
					</tr>
					@endforeach
				</tbody>
			</table>
		</div>

		<div class="sales chart sales-chart">
			<div class="ui fluid card">
				<div class="content top">
				  <img loading="lazy" class="left floated mini ui image mb-0" src="{{ asset_('assets/images/chart.png') }}">
				  <div class="ui form right floated">
				  	<input type="date" id="sales-date" name="sales_date" value="{{ date('Y-m-d') }}">
				  </div>
				  <div class="header"><h3>{{ __('Sales') }}</h3></div>
				  <div class="meta">
				    <span class="date">{{ __('Sales evolution per month') }}</span>
				  </div>
				</div>
				<div class="content grid">
					<div class="wrapper">
						@foreach($sales_steps as $step)
				      <div class="row">
				      	<div>{{ ceil($step) }}</div>
				      	@for($k = 0; $k <= (count($sales_per_day) - 1); $k++)
				      	<div>
				      		@if($step == 0)
						      <span data-tooltip="{{ __(':count sales', ['count' => $sales_per_day[$k] ?? '0']) }}" style="height: {{ $sales_per_day[$k] > 0 ? ($sales_per_day[$k] / $max_value * 305) : '1' }}px"><i class="circle blue icon mx-0"></i></span>
						      @endif
						    </div>
				      	@endfor
				      </div>
						 @endforeach

						<div class="row">
							<div>-</div>
							@for($day = 1; $day <= count($sales_per_day); $day++)
							<div>{{ $day }}</div>
							@endfor
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>


	<div class="ui two stackable cards latest mt-1">
		<div class="card fluid">
			<table class="ui celled unstackable table borderless">
				<thead>
					<tr>
						<th class="left aligned w-auto"><h3>{{ __('Latest newsletter subscribers') }}</h3></th>
						<th class="left aligned">{{ __('Date') }}</th>
					</tr>
				</thead>
				<tbody>
					@foreach($newsletter_subscribers as $newsletter_subscriber)
					<tr>
						<td class="capitalize">{{ $newsletter_subscriber->email }}</td>
						<td>{{ $newsletter_subscriber->created_at }}</td>
					</tr>
					@endforeach
				</tbody>
			</table>
		</div>

		<div class="card fluid">
			<table class="ui celled unstackable table borderless">
				<thead>
					<tr>
						<th class="left aligned w-auto"><h3>{{ __('Latest reviews') }}</h3></th>
						<th class="left aligned">{{ __('Review') }}</th>
						<th>{{ __('Date') }}</th>
					</tr>
				</thead>
				<tbody>
					@foreach($reviews as $review)
					<tr>
						<td><a href="{{ route('home.product', ['id' => $review->product_id, 'slug' => $review->product_slug.'#reviews']) }}">{{ $review->product_name }}</a></td>
						<td><div class="ui star small rating" data-rating="{{ $review->rating }}" data-max-rating="5"></div></td>
						<td>{{ $review->created_at }}</td>
					</tr>
					@endforeach
				</tbody>
			</table>
		</div>
	</div>

	<div class="ui three stackable cards user-agent">
		<div class="fluid card">
			<div class="content header">
				<div class="content header">
				<img src="/assets/images/glob-hits.png">
				<div class="title">
					<div><h3>{{ __('Devices') }}</h3></div>
					<div>{{ __('User devices') }}</div>
				</div>
			</div>
			</div>
			<div class="content body">
				@foreach($devices as $name => $count)
				<div class="item">
					<div class="name">{{ __(mb_ucfirst($name)) }}</div>
					<div class="count">{{ $count }}%</div>
				</div>
				@endforeach
			</div>
		</div>
		
		<div class="fluid card">
			<div class="content header">
				<div class="content header">
				<img src="/assets/images/glob-hits.png">
				<div class="title">
					<div><h3>{{ __('Operating systems') }}</h3></div>
					<div>{{ __('User Operating systems') }}</div>
				</div>
			</div>
			</div>
			<div class="content body">
				@foreach($operating_systems as $name => $count)
				<div class="item">
					<div class="name">{{ __(mb_ucfirst($name)) }}</div>
					<div class="count">{{ $count }}%</div>
				</div>
				@endforeach
			</div>
		</div>

		<div class="fluid card">
			<div class="content header">
				<div class="content header">
				<img src="/assets/images/glob-hits.png">
				<div class="title">
					<div>{{ __('Browsers') }}</div>
					<div>{{ __('User browsers') }}</div>
				</div>
			</div>
			</div>
			<div class="content body">
				@foreach($browsers as $name => $count)
				<div class="item">
					<div class="name">{{ __(mb_ucfirst($name)) }}</div>
					<div class="count">{{ $count }}%</div>
				</div>
				@endforeach
			</div>
		</div>
	</div>

	<div class="ui two stackable cards countries">
		<div class="card fluid world-map">
			<div class="content header">
				<img src="/assets/images/glob-hits.png">
				<div class="title">
					<div><h3>{{ __('Visits by country') }}</h3></div>
					<div>{{ __('Total visits by country') }}</div>
				</div>
			</div>
			<div class="content body" id="world-map">
				{!! load_world_map($traffic) !!}
			</div>
		</div>

		<div class="card fluid traffic-countries">
			<div class="content header">
				<img src="/assets/images/glob-hits.png">
				<div class="title">
					<div><h3>{{ __('Countries') }}</h3></div>
					<div>{{ __('Visits sorted by countries') }}</div>
				</div>
			</div>

			<div class="content body">
				<div class="items">
					@foreach($countries_traffic as $name => $count)
					<div class="item">
						<div class="name">{{ __(mb_ucfirst($name)) }}</div>
						<div class="count"><span>{{ $count }}</span></div>
					</div>
					@endforeach
				</div>
			</div>
		</div>
	</div>
	
	<script type="application/javascript">
		'use strict';
		
		$(() => 
		{
			$('#sales-date').on('change', function()
			{
				$.post('{{ route('admin.update_sales_chart') }}', {date: $(this).val()})
    		.done(function(data)
    		{
    			window.data = data
    			$('.sales-chart .content.grid').html(data.html);
    		})
			})

			@if(config('app.report_errors'))
			$.post('/admin/report_errors');
	    @endif
		})
	</script>

</div>

@endsection