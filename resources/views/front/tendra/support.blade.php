@extends(view_path('master'))

@section('additional_head_tags')
@if(captcha_is_enabled('contact') && captcha_is('google'))
{!! google_captcha_js() !!}
@endif

<style>
	body, html {
		background: #fff;
	}
</style>
@endsection

@section('body')

	<div id="support">
		<div class="header">
			<div class="heading">{{ __('Support') }}</div>
			<div class="subheading">{{ __('Help, contact and frequently asked questions') }}</div>
		</div>

		<div class="body">
			<div class="container">
				<div class="left-side">
					<div class="content">
						{!! $support->content ?? null !!}
					</div>

					@if($faqs->count())
					<div class="faq">
						<div class="title"><i class="right angle icon mr-1-hf"></i>{{ __('Frequently asked questions') }}</div>
						<div class="content">
							<div class="ui borderless segments">
								@foreach($faqs as $faq)
							  <div class="ui borderless segment {{ $loop->first ? 'active' : 0 }}">
							    <p><i class="circle outline icon"></i>{{ $faq->question }}</p>
							    <div>
							    	{!! $faq->answer !!}
							    </div>
							  </div>
							  @endforeach
							</div>	
						</div>
					</div>
					@endif
				</div>

				<div class="right-side">
					<div class="contact">
						<div class="title">{{ __('Contact us') }}</div>

						<form action="{{ route('home.support') }}" method="post" class="ui large form">
							@csrf

							@if(session('support_response'))
							<div class="ui fluid message mx-auto">
								<i class="close icon"></i>
								{{ session('support_response') }}
							</div>
							@endif
							
							<div class="field">
								<label>{{ __('Email') }}</label>
								<input type="email" value="{{ old('email', request()->user()->email ?? '') }}" name="email" placeholder="Your email..." required>
							</div>

							<div class="field">
								<label>{{ __('Subjet') }}</label>
								<input type="text" name="subject" value="{{ old('subject') }}" placeholder="..." required>
							</div>

							<div class="field">
								<label>{{ __('Question') }}</label>
								<textarea name="message" cols="30" rows="10" placeholder="..." required>{{ old('message') }}</textarea>
							</div>

					    @if(captcha_is_enabled('contact'))
					    <div class="field d-flex justify-content-center">
					      {!! render_captcha() !!}

					      @if(captcha_is('mewebstudio'))
					      <input type="text" name="captcha" value="{{ old('captcha') }}" class="ml-1">
					      @endif
					    </div>
					    @endif

							<div class="field">
								<button class="ui large button" type="submit">{{ __('Submit') }}</button>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>

@endsection