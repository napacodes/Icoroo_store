<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>{{ __('Item update') }}</title>
	</head>

	<body>
		<div style="height: 100vh;background: aliceblue;width: 100%;display: flex;">
		  <table style="max-width: 600px;margin: auto;background: #fff;">
		    <tbody>
		    	<tr>
			      <td style="padding: 1rem;background: #2f2f2f;">
			      	<img alt="{{ config('app.name') }}" src="{{ secure_asset("storage/images/".config('app.logo')) }}" style="width: auto;height: 50px;">
			      </td>
			    </tr>
			    <tr>
			      <td style="padding: 1.5rem;">
				      <p style="font-size: 1.2rem;font-family: system-ui;margin-top: 0;">{{ __('Hi there') }},</p>
				      <p style="font-size: 1.2rem;font-family: system-ui;line-height: 1.5;margin-bottom: 0;">
				      	{{ __("We'd like to let you know that an update to your item ':item_name' is now available in your Downloads page.", 
						      					['item_name' => $product['name']]) }}
							</p>
							<p style="font-size: 1.2rem;font-family: system-ui;line-height: 1.5;margin-bottom: 0;">
								{{ __('Remember: you need to be logged in to download the update.') }}
							</p>
			      </td>
			    </tr>
			    <tr>
			      <td style="padding: 1.5rem;">
			        <a href="{{ $product['link'] }}" style="background-image: url('{{ $product['cover'] }}');width: 100px;height: 100px;background-position: center;background-size: cover;margin-right: 1rem;float: left;display: inline-block;text-decoration: none;color: #000;"></a>
			        <a href="{{ $product['link'] }}" style="font-size: 1.2rem;font-family: system-ui;display: inline-block;text-decoration: none;color: #000;">
						    <p style="margin-top: .5rem;margin-bottom: .5rem;">{{ $product['name'] }}</p>
						    <p style="margin: 0;">{{ __('Update') }} : {{$product['updated_at']  }}</p>
							</a>
			      </td>
			    </tr>
			    <tr>
			      <td style="padding: 1rem 1.5rem;background: #ff5151;">
			      	<p style="font-size: 1.1rem;font-family: system-ui;color: #fff;margin: 0;line-height: 1.5;font-weight: 600;">{{ __('Copyright') }} {{ config('app.name') }} {{ date('Y') }}</p></td>
			    </tr>
			  </tbody>
			</table>
		</div>
	</body>
</html>