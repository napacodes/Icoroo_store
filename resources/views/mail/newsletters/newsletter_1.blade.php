<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

		<style>
			@import url('https://fonts.googleapis.com/css2?family=Spartan:wght@400;500;600;700&display=swap'); 
			
			p, h1, h2, h3, h4, ol, li, ul, th, td, span, a {  
				font-family: 'Spartan', sans-serif;
				line-height: 1.5;
			}
		</style>
	</head>

	<body dir="ltr" style="margin: 0; background: aliceblue;">
		<table style="height: 100vh;background: #fff;border-spacing: 0;border: none;min-width: 650px;overflow: auto;max-width: 650px;margin: auto;">
			<thead>
				<tr>
					<td>
						<div style="max-width: 650px;margin: 0 auto;background: #fff;height: 100%;">
							<div style="height: 70px;display: flex;align-items: center;padding: .5rem 1rem;background: #ffff;">
								<img style="height: auto;width: 160px;" src="{{ asset("storage/images/".config('app.logo')) }}" alt="{{ config('app.name') }}">
							</div>
							<div style="background-image: url('/storage/images/newsletter_1_top_cover.jpg');background-position: center;background-size: cover;min-height: 280px;padding: 1rem;">
								<div style="margin: 1rem auto;font-size: 1.5rem;text-align: center;font-weight: 700;color: #fff;background: #00000014;padding: 1rem;backdrop-filter: blur(5px);max-width: 500px;width: 100%;" contenteditable>
									Lorem ipsum dolor sit, amet consectetur adipisicing elit.
								</div>
								<div style="color: #fff;font-size: 1.3rem;text-align: center;font-weight: 500;max-width: 450px;margin: 1rem auto;" contenteditable>
									Lorem ipsum dolor sit amet consectetur adipisicing elit. Sit labore odit nesciunt autem dolorum
								</div>
							</div>
						</div>
					</td>
				</tr>		
			</thead>
			<tbody>
				<tr>
					<td>
						<div class="products" style="max-width: 650px;margin: 0 auto;background: #fff;height: 100%;">
							<div style="padding: 2rem 2rem 1rem;text-align: center;font-weight: 600;font-size: 1.6rem;color: #000;text-transform: capitalize;max-width: 500px;margin: auto;" contenteditable>
								Lorem ipsum dolor sit amet consectetur
							</div>
							<div style="padding: 0 1rem;display: inline-block;">
								<a class="item" style="margin: 0.5rem;max-width: 188px;width: 100%;display: block;text-decoration: none;color: #000;float: left;" href="">
									<div class="cover" style="width:188px;height:188px;background-image: url('/storage/covers/placeholder.webp');background-position: center;background-size: cover;"></div>
									<div class="text" style="text-align: center;padding: .75rem .5rem;font-weight: 500;">Iste dicta accusantium iure eveniet minima in</div>
								</a>
								<a class="item" style="margin: 0.5rem;max-width: 188px;width: 100%;display: block;text-decoration: none;color: #000;float: left;" href="">
									<div class="cover" style="width:188px;height:188px;background-image: url('/storage/covers/placeholder.webp');background-position: center;background-size: cover;"></div>
									<div class="text" style="text-align: center;padding: .75rem .5rem;font-weight: 500;">Velit, labore, aliquid. Quia dolor voluptatem</div>
								</a>
								<a class="item" style="margin: 0.5rem;max-width: 188px;width: 100%;display: block;text-decoration: none;color: #000;float: left;" href="">
									<div class="cover" style="width:188px;height:188px;background-image: url('/storage/covers/placeholder.webp');background-position: center;background-size: cover;"></div>
									<div class="text" style="text-align: center;padding: .75rem .5rem;font-weight: 500;">Perferendis rerum cumque ea, sunt repudiandae amet nisi et, vel, laudantium minus.</div>
								</a>
								<a class="item" style="margin: 0.5rem;max-width: 188px;width: 100%;display: block;text-decoration: none;color: #000;float: left;" href="">
									<div class="cover" style="width:188px;height:188px;background-image: url('/storage/covers/placeholder.webp');background-position: center;background-size: cover;"></div>
									<div class="text" style="text-align: center;padding: .75rem .5rem;font-weight: 500;">Veritatis nobis modi libero harum architecto officia ipsa, porro ut inventore</div>
								</a>
								<a class="item" style="margin: 0.5rem;max-width: 188px;width: 100%;display: block;text-decoration: none;color: #000;float: left;" href="">
									<div class="cover" style="width:188px;height:188px;background-image: url('/storage/covers/placeholder.webp');background-position: center;background-size: cover;"></div>
									<div class="text" style="text-align: center;padding: .75rem .5rem;font-weight: 500;">Deleniti ex enim id quos veniam distinctio eveniet velit</div>
								</a>
								<a class="item" style="margin: 0.5rem;max-width: 188px;width: 100%;display: block;text-decoration: none;color: #000;float: left;" href="">
									<div class="cover" style="width:188px;height:188px;background-image: url('/storage/covers/placeholder.webp');background-position: center;background-size: cover;"></div>
									<div class="text" style="text-align: center;padding: .75rem .5rem;font-weight: 500;">Magnam laborum provident inventore vel nulla voluptatem culpa et</div>
								</a>
							</div>
						</div>

						@if(config('app.subscriptions.enabled'))
						<div class="subscriptions" style="max-width: 650px;margin: 0 auto;height: 100%;background-image: url('/storage/images/newsletter_1_pricing_cover.jpg');background-size: cover;background-position: center;padding-bottom: 1rem;">
							<div style="padding: 2rem 2rem 1rem;text-align: center;font-weight: 600;font-size: 1.6rem;color: #fff;" contenteditable>
								Our Popular Subscription
							</div>

							<div class="items" style="padding: 0 1rem;display: flex;">
								<div class="item" style="margin: 0.5rem;max-width: 190px;background: #00000047;position: relative;width: 100%;">
									<div class="link" style="position: absolute;right: 1rem;top: 1rem; cursor:pointer;">
										<img src="/assets/images/link.webp" style="width: 20px;height: 20px;filter: invert(1);">
									</div>

									<div style="color: #ffb9b9;padding: 1rem 1rem .5rem;text-align: center;font-size: 1.2rem;font-weight: 600;" contenteditable>
										Premium membership
									</div>
									<div style="padding: 0 1rem 1rem;text-align: center;font-size: 1.3rem;font-weight: 600;color: #fff9f9;" contenteditable>
										USD 99.00/mo
									</div>
									<div style="padding: 0 1rem 1rem;color: #fff;text-align: center;">
										<div style="padding: .5rem 0;font-size: .9rem;border-bottom: 2px dashed #fff;display: table;margin: auto;" contenteditable>
											20 Downloads
										</div>
										<div style="padding: .5rem 0;font-size: .9rem;border-bottom: 2px dashed #fff;display: table;margin: auto;" contenteditable>
											Life time access
										</div>
										<div style="padding: .5rem 0;font-size: .9rem;border-bottom: 2px dashed #fff;display: table;margin: auto;" contenteditable>
											10% OFF for every purchase
										</div>
										<div style="padding: .5rem 0;font-size: .9rem;display: table;margin: auto;" contenteditable>
											Money back guarantee
										</div>
									</div>
									<a class="subscription-link" style="display: table;margin: auto;padding: .5rem 1rem;background: rgb(10 10 10 / 46%);color: #fff;font-weight: 600;margin-bottom: 1rem;text-decoration: none;" href="" contenteditable>{{ __('Get started') }}</a>
								</div>

								<div class="item" style="margin: 0.5rem;max-width: 190px;background: #00000047;position: relative;width: 100%;">
									<div class="link" style="position: absolute;right: 1rem;top: 1rem; cursor:pointer;">
										<img src="/assets/images/link.webp" style="width: 20px;height: 20px;filter: invert(1);">
									</div>

									<div style="color: #fffab9;padding: 1rem 1rem .5rem;text-align: center;font-size: 1.2rem;font-weight: 600;" contenteditable>
										Golden membership
									</div>
									<div style="padding: 0 1rem 1rem;text-align: center;font-size: 1.3rem;font-weight: 600;color: #fff9f9;" contenteditable>
										USD 450.00/mo
									</div>
									<div style="padding: 0 1rem 1rem;color: #fff;text-align: center;">
										<div style="padding: .5rem 0;font-size: .9rem;border-bottom: 2px dashed #fff;display: table;margin: auto;" contenteditable>
											Unlimited Downloads
										</div>
										<div style="padding: .5rem 0;font-size: .9rem;border-bottom: 2px dashed #fff;display: table;margin: auto;" contenteditable>
											Life time access
										</div>
										<div style="padding: .5rem 0;font-size: .9rem;border-bottom: 2px dashed #fff;display: table;margin: auto;" contenteditable>
											50% OFF for every purchase
										</div>
										<div style="padding: .5rem 0;font-size: .9rem;display: table;margin: auto;" contenteditable>
											Money back guarantee
										</div>
									</div>
									<a class="subscription-link" style="display: table;margin: auto;padding: .5rem 1rem;background: rgb(10 10 10 / 46%);color: #fff;font-weight: 600;margin-bottom: 1rem;text-decoration: none;" href="" contenteditable>{{ __('Get started') }}</a>
								</div>

								<div class="item" style="margin: 0.5rem;max-width: 190px;background: #00000047;position: relative;width: 100%;">
									<div class="link" style="position: absolute;right: 1rem;top: 1rem; cursor:pointer;">
										<img src="/assets/images/link.webp" style="width: 20px;height: 20px;filter: invert(1);">
									</div>

									<div style="color: #b9fdff;padding: 1rem 1rem .5rem;text-align: center;font-size: 1.2rem;font-weight: 600;" contenteditable>
										Ultimate membership
									</div>
									<div style="padding: 0 1rem 1rem;text-align: center;font-size: 1.3rem;font-weight: 600;color: #fff9f9;" contenteditable>
										USD 890.00/mo
									</div>
									<div style="padding: 0 1rem 1rem;color: #fff;text-align: center;">
										<div style="padding: .5rem 0;font-size: .9rem;border-bottom: 2px dashed #fff;display: table;margin: auto;" contenteditable>
											Unlimited Downloads
										</div>
										<div style="padding: .5rem 0;font-size: .9rem;border-bottom: 2px dashed #fff;display: table;margin: auto;" contenteditable>
											Life time access
										</div>
										<div style="padding: .5rem 0;font-size: .9rem;border-bottom: 2px dashed #fff;display: table;margin: auto;" contenteditable>
											70% OFF for every purchase
										</div>
										<div style="padding: .5rem 0;font-size: .9rem;display: table;margin: auto;" contenteditable>
											Money back guarantee
										</div>
									</div>
									<a class="subscription-link" style="display: table;margin: auto;padding: .5rem 1rem;background: rgb(10 10 10 / 46%);color: #fff;font-weight: 600;margin-bottom: 1rem;text-decoration: none;" href="" contenteditable>{{ __('Get started') }}</a>
								</div>
							</div>
						</div>
						@endif
					</td>
				</tr>
			</tbody>
			<tfoot>
				<tr>
					<td>
						<div style="max-width: 650px;margin: 0 auto;background: #fff;height: 100%;padding: 3rem 0 0;text-align: left;">
							<div style="font-size: .8rem;color: #000000;max-width: 500px;margin: 0 auto 1rem; padding: 0 1rem;" contenteditable>
								{{ __('This commercial email was sent to you by vAlexa digital market.') }}
							</div>
							<div style="font-size: .8rem;max-width: 500px;margin: auto;color: #000;padding: 0 1rem;" contenteditable>
								{!! __("You received this email because you registered, accepted an invitation, or have shopped from our digital market. And this is to let you be informed of our new discounts and offers. We respect and will protect well your privacy. If you don't want to receive our email or received in error, you can easily <a href='/'>Unsubscribe</a> from our newsletter.") !!}
							</div>
							<div style="padding: 1rem;font-size: .9rem;text-align: center;background: #ffffff;margin-top: 3rem;color: #000;border-top: 3px dashed aliceblue;">
								<div style="max-width: 450px;margin: auto;" contenteditable>{{ __('Copyright © :date :name Digital Marketplace.', ['date' => "2015-".date('Y'), 'name' => config('app.name')]) }}</div>
								<div style="max-width: 450px;margin: auto;" contenteditable>{{ __('All Rights Reserved') }}</div>
							</div>
						</div>
					</td>
				</tr>
			</tfoot>
		</table>
	</body>
</html>