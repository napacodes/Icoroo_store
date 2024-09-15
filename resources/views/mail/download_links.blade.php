<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<style type="text/css"> 
			p, h1, h2, h3, h4, ol, li, ul, th, td, span {  
				font-family: 'system-ui';
				line-height: 1.5;
			} 
		</style>
	</head>
    
	<body>
		<table style="height: 100%; width: 100%; min-height: 500px; background: ghostwhite; padding: 1rem;">
			<tbody><tr><td>
				<table style="max-width: 600px;width: 100%; min-width: 480px;margin: auto;background: #fff;border-radius: 1rem;padding: 1.5rem;">
					<thead>
						<tr>
							<th>
								<div style="padding:1rem;font-size: 3rem;color: #000;">{{ config('app.name') }}</div>
								<div style="margin-bottom:2rem;height:.25rem;background: #4e4e4e;border-radius:100%"></div>
							</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>
								<div style="font-size: 1.4rem; text-align: center; font-weight: 600; margin-bottom: 2rem;">{{ __('Download links for order number :order_id', ['order_id' => '5ZA1F3A5Z1F']) }}</div>

								<div style="margin: 1rem 0 1.5rem; padding: 0 .25rem; font-size: 1rem;">{{ __('Hi there, please use the links below to download your items') }}</div>

								<div style="border-radius: .75rem; overflow: hidden; border: 1px solid #c7c7c7;">
									@foreach($items as $item)
									<div style="display: flex; font-size: 1rem; line-height: 1.3; padding: 1rem; font-weight: 500; {{ !$loop->last ? 'border-bottom: 1px solid #c7c7c7;' : '' }}">
										<div style="margin: 0.25rem; padding: 0.25rem 0.5rem;">{{ $item->name }}</div>
										<div style="margin-left: auto;text-align: right;">
											@if($item->file)
											<a href="{{ $item->file }}" style="margin: .25rem;padding: .25rem .5rem;text-decoration: none;color: #000;background: #ffff64;border-radius: .5rem;display: inline-block;">{{ __('Main File') }}</a>
											@endif

											@if($item->license)
											<a href="{{ $item->license }}" style="margin: .25rem;padding: .25rem .5rem;text-decoration: none;color: #fff;background: #ff733c;border-radius: .5rem;display: inline-block;">{{ __('License') }}</a>
											@endif

											@if($item->key)
											<a href="{{ $item->key }}" style="margin: .25rem;padding: .25rem .5rem;text-decoration: none;color: #fff;background: #765bde;border-radius: .5rem;display: inline-block;">{{ __('Key') }}</a>
											@endif
										</div>
									</div>
									@endforeach
								</div>
							</td>
						</tr>
					</tbody>
					<tfoot>
						<tr>
							<td>
								<div style="margin-top: 4rem; text-align: right; padding: 0 1rem; margin-left: auto; display: table;">
									<a style="text-decoration: none; font-size: .9rem; font-weight: 600; color: #c1c1c1;" href="/" target="_blank">
										{{ __(':name © 2023 All right reserved', ['name' => config('app.name')]) }}
									</a>
								</div>
							</td>
						</tr>
					</tfoot>
				</table>			
			</td></tr></tbody>
		</table>
	</body>

</html>