<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<style type="text/css"> 
			@import url('//fonts.googleapis.com/css2?family=Spartan:wght@400;600&display=swap');  
			p, h1, h2, h3, h4, ol, li, ul, th, td, span {  
				font-family: 'Spartan', sans-serif;
				line-height: 1.5;
			} 
		</style>
	</head>
    
	<body style="margin: 0">
		<table style="width: 100%; padding: 1rem;">
			<tbody>
				<tr>
					<td>
						<div style="max-width: 600px;width: 100%;margin: auto;;">
							<div style="font-size: 2.5rem; font-weight: 700; text-align: center; padding-bottom: 1rem;">
								<img src="{{ secure_asset('assets/images/email.png') }}" style="width: 80px;height: 80px;">
							</div>
							
							<div>
								<div style="min-height: 180px;">
									<div style="background: #ffffff;text-align: center;">
										<div style="font-weight: 600;font-size: 1.3rem; color: #000;padding-bottom:1rem;font-family: system-ui;">{{ __("From") }} : {{ $user_email }}</div>
										<div style="height: 8px;background: linear-gradient(45deg, #3e91ff, #85ff6a, #358eff);border-radius: 10px;"></div>
									</div>
									<div style="font-size: 1.3rem;font-family: system-ui;font-weight: 600;margin-bottom: 1rem;color: #3d3d3d;padding: 2rem 2rem 0;">{{ $subject }}</div>
									<div style="padding: 0 2rem 2rem;font-family: system-ui;font-size: 1.3rem;">
										{!! nl2br(strip_tags($text)) !!}
									</div>
								</div>
							</div>

							<div style="text-align: center;font-family: system-ui;font-size: 1.2rem;font-weight: 600;color: #000000;padding: 1rem;background: #f9f9f9;border-radius: 0 0 .75rem .75rem;border: 1px solid #e5e5e5;border-top: 0;">{{ __(':name © :year All right reserved', ['name' => config('app.name'), 'year' => date('Y')]) }}</div>
						</div>
					</td>
				</tr>
			</tbody>
		</table>
	</body>

</html>