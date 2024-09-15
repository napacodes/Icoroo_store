<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
		<title>{{ __('Invoice') }}</title>
		<script src="/assets/html-to-image.min.js"></script>
	</head>

	<body style="margin: 0;display: flex;align-items: center;justify-content: center;min-height: 100vh;max-width: 1100px;">
		<a href="" id="download" download="invoice-{{ "{$reference}-{$date}" }}.png" style="display: none;"></a>
		<div id="container" style="padding: 6.5rem; font-size: 1.2rem; line-height: 1.3; font-family: system-ui; box-sizing: border-box; max-width: 1100px; margin: auto; background: #fff; position: relative; height: 1555px;">
			<div class="top-mask" style="position: absolute; right: 0; top: 0; width: 320px; height: 320px; z-index: 0; -webkit-mask-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiB2aWV3Qm94PSIwIDAgMzAwIDMwMCI+PGRlZnM+PHN0eWxlPi5he2ZpbGw6bm9uZTt9LmJ7Y2xpcC1wYXRoOnVybCgjYSk7fS5je2ZpbGw6IzY0NGVhMDt9PC9zdHlsZT48Y2xpcFBhdGggaWQ9ImEiPjxyZWN0IGNsYXNzPSJhIiB4PSItMC4yNSIgeT0iLTAuMjUiIHdpZHRoPSIzMDAiIGhlaWdodD0iMzAwIi8+PC9jbGlwUGF0aD48L2RlZnM+PHRpdGxlPm1hc2s8L3RpdGxlPjxnIGNsYXNzPSJiIj48cGF0aCBjbGFzcz0iYyIgZD0iTTI5OS41MSwyOTkuNTFjLTI4LTQwLjA4LTU5LjktNTkuNzktMTE3Ljc1LTc0LjctODUuMTItMjEuOTUsNDItNzksOS42MS0xMTYuMzctMjAuNjYtMjkuMjQtMTA2LjEsMzkuNzMtOTcuNTgtMjkuMkMxMDAuMjYsMjYuOTQtMS44Ni0xLjMyLDAsMEwyOTkuNTEtLjQ5WiIvPjwvZz48L3N2Zz4='); -webkit-mask-position: right; -webkit-mask-size: cover; background: powderblue;"></div>
			<div class="header" style="display: flex; max-width: 820px; margin: auto;">
				<div class="column" style="padding: 2rem; flex: 1;">
					<div class="logo" style="margin-bottom: 2rem;">
						<img src="{{ config('logo_b64') }}" style="width: 220px;">
					</div>
					<div class="heading" style="font-size: 1.3rem; font-weight: 600; margin-bottom: 0.5rem; display: table; min-width: 150px;">
						{{ __('Invoice to') }} :
					</div>
					<div class="customer">
						<div class="name" style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem;">{{ $buyer_name }}</div>
						<div class="email" style="margin-bottom: 0.5rem; font-size: 1.1rem; color: #484848; text-transform: capitalize;">{{ $buyer_email }}</div>
						{{-- <div class="phone" style="font-size: 1.1rem; color: #484848; letter-spacing: 0.5px;">+000 999 999 999</div> --}}
					</div>
				</div>
				<div class="column" style="padding: 2rem; flex: 1; text-align: right;">
					<div class="heading" style="font-size: 2rem; font-weight: 700; text-transform: uppercase; padding-bottom: 1rem; display: table; margin-left: auto; margin-bottom: 1rem; position: relative;">
						{{ __('Invoice') }}
					</div>
					<div class="date">
						<div class="title" style="font-weight: 600;">{{ __('Date') }}</div>
						<div class="text">{{ $date }}</div>
					</div>
					<div class="reference" style="margin-top: 1rem;">
						<div class="title" style="font-weight: 600;">{{ __('Reference') }}</div>
						<div class="text">{{ $reference }}</div>
					</div>
				</div>
			</div>
			<div class="body" style="padding: 1rem 2rem;">
				<div class="items">
					<div class="header" style="margin-bottom: 1rem;">
						<div class="item" style="display: flex; justify-content: space-between; background: linear-gradient(45deg, #ffb757, tomato, #ff4423); color: #fff; font-weight: 600; border-radius: 1rem;">
							<div class="quntity" style="text-align: center; flex: 1; padding: 1rem;">{{ __('Qty') }}</div>
							<div class="name" style="padding: 1rem; width: 50%; flex: none; text-align: left;">{{ __('Item name') }}</div>
							<div class="price" style="text-align: center; flex: 1; padding: 1rem;">{{ __('Unit price') }}</div>
							<div class="total" style="text-align: center; flex: 1; padding: 1rem;">{{ __('Total') }}</div>
						</div>
					</div>
					<div class="body">
						@foreach($items ?? [] as $item)
						<div class="item" style="display: flex; justify-content: space-between;">
							<div class="quntity" style="text-align: center; flex: 1; padding: 1rem;">1</div>
							<div class="name" style="padding: 1rem; width: 50%; flex: none; text-align: left; text-transform: capitalize;">{{ $item['name'] }}</div>
							<div class="price" style="text-align: center; flex: 1; padding: 1rem;">{{ price($item['value'], 0) }}</div>
							<div class="total" style="text-align: center; flex: 1; padding: 1rem;">{{ price($item['value'], 0) }}</div>
						</div>
						@endforeach

						@if(count($items ?? []) < 6)
						@for($i=0; $i < (6 - count($items ?? [])); $i++)
						<div class="item" style="display: flex; justify-content: space-between;">
							<div class="quntity" style="text-align: center; flex: 1; padding: 1rem;">&nbsp;</div>
							<div class="name" style="padding: 1rem; width: 50%; flex: none; text-align: left; text-transform: capitalize;">&nbsp;</div>
							<div class="price" style="text-align: center; flex: 1; padding: 1rem;">&nbsp;</div>
							<div class="total" style="text-align: center; flex: 1; padding: 1rem;">&nbsp;</div>
						</div>
						@endfor
						@endif
					</div>
					<div class="footer" style="margin-top: 1rem; padding-top: 1rem; border-top: 5px solid whitesmoke;">
						<div class="summary" style="min-width: 350px; margin-left: auto; text-align: right; display: table;">
							<div class="subtotal" style="display: flex; padding: 1rem 1.5rem; justify-content: space-between; font-weight: 600; border-bottom: 5px solid #fff;">
								<div class="title" style="margin-right: 1rem;">{{ __('Subtotal') }}</div>
								<div class="amount" style="font-weight: 400;">{{ price($subtotal, 0) }}</div>
							</div>
							<div class="fee" style="display: flex; padding: 1rem 1.5rem; justify-content: space-between; font-weight: 600; border-bottom: 5px solid #fff; background: whitesmoke; border-radius: 1rem;">
								<div class="title" style="margin-right: 1rem;">{{ __('Handling fee') }}</div>
								<div class="amount" style="font-weight: 400;">{{ price($fee, 0) }}</div>
							</div>
							<div class="tax" style="display: flex; padding: 1rem 1.5rem; justify-content: space-between; font-weight: 600; border-bottom: 5px solid #fff;">
								<div class="title" style="margin-right: 1rem;">{{ __('Tax & VAT') }}</div>
								<div class="amount" style="font-weight: 400;">{{ price($tax, 0) }}</div>
							</div>
							<div class="tax" style="display: flex; padding: 1rem 1.5rem; justify-content: space-between; font-weight: 600; border-bottom: 5px solid #fff;">
								<div class="title" style="margin-right: 1rem;">{{ __('Discount') }}</div>
								<div class="amount" style="font-weight: 400;">{{ price($discount, 0) }} </div>
							</div>
							<div class="total" style="display: flex; padding: 1rem 1.5rem; justify-content: space-between; font-weight: 600; border-radius: 1rem; color: #fff; background: #98cdd4;">
								<div class="title" style="margin-right: 1rem;">{{ __('Total Due') }}</div>
								<div class="amount" style="font-weight: 400;">{{ price($total_due, 0) }}</div>
							</div>
						</div>

						<div class="terms" style="margin-top: 3rem;">
							<div class="heading" style="font-size: 1.5rem; margin-bottom: 0.5rem; font-weight: 600;">{{ __('Terms and conditions') }}</div>
							@if(config('app.invoice.tos'))
							<div class="text">{{ config('app.invoice.tos') }}</div>
							@endif
						</div>
					</div>
				</div>
			</div>
			<div class="footer" style="padding: 2rem 8.5rem; position: absolute; bottom: 0; width: 100%; left: 0; box-sizing: border-box; background: #f5f5f5;">
				<div class="items" style="display: flex; width: 100%; justify-content: space-between;">
					<div class="item" style="display: flex; font-size: 1rem; align-items: center; margin-right: 2rem;">
						<div class="icon" style="margin-right: 0.75rem;"><img src="data:image/png;base64,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" style="width: 40px;"></div>
						<div class="content">
							<div class="heading" style="font-weight: 600;">{{ __('Website') }}</div>
							<div class="text">{{ config('app.url') }}</div>
						</div>
					</div>
					<div class="item" style="display: flex; font-size: 1rem; align-items: center; margin-right: 2rem;">
						<div class="icon" style="margin-right: 0.75rem;"><img src="data:image/png;base64,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" style="width: 40px;"></div>
						<div class="content">
							<div class="heading" style="font-weight: 600;">{{ __('Address') }}</div>
							<div class="text">1148 Fulton Ave Sacramento California</div>
						</div>
					</div>
					<div class="item" style="display: flex; font-size: 1rem; align-items: center;">
						<div class="icon" style="margin-right: 0.75rem;"><img src="data:image/png;base64,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" style="width: 40px;"></div>
						<div class="content">
							<div class="heading" style="font-weight: 600;">{{ __('Email') }}</div>
							<div class="text">{{ config('app.email') }}</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<script>
			let el = document.querySelector('#container');
			
			htmlToImage.toPng(el)
			.then(dataUrl => 
			{
			    let a = document.querySelector('#download');
			    a.href = dataUrl;
			    a.click();
			})
			.catch(error => {

			})
			.finally(() => 
			{
				self.close();
			});
		</script>
	</body>
</html>