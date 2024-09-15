@extends(view_path('master'))

@section('additional_head_tags')
@if(captcha_is_enabled('contact') && captcha_is('google'))
{!! google_captcha_js() !!}
@endif
@endsection

@section('body')
	<div id="support">
		<div class="header">
			<div class="title">{{ __('Support') }}</div>
		</div>

		<div class="body">
			<div class="left-side">
				<div class="content">
					{!! $support->content ?? null !!}
				</div>

				@if($faqs->count())
				<div class="faq">
					<div class="header">{{ __('Frequently asked questions') }}</div>
					<div class="items">
						@foreach($faqs as $faq)
					  <div class="item">
					  	<div class="header">
					  		<img src="{{ asset_('assets/images/plus.png') }}">
					  		<div class="question">{{ $faq->question }}</div>
					  	</div>
					    <div class="answer">
					    	{!! $faq->answer !!}
					    </div>
					  </div>
					  @endforeach
				  </div>
				</div>
				@endif
			</div>
			
			<div class="right-side">
				<div class="ui sticky sticky-form">
				  <form action="{{ route('home.support') }}" method="post" class="ui large form">
				    @csrf

				    @if(session('support_response'))
						<div class="ui small bold positive message mx-auto">
							{{ session('support_response') }}
						</div>
						@endif

				    <div class="field">
				    	<label>{{ __('Email') }}</label> 
				    	<input type="email" value="notifyph@gmail.com" name="email" placeholder="Your email..." required>
				    </div>
				    
				    <div class="field">
				    	<label>{{ __('Subjet') }}</label> 
				    	<input type="text" name="subject" value="" placeholder="..." required>
				    </div>

				    <div class="field">
				    	<label>{{ __('Question') }}</label> 
				    	<textarea name="message" cols="30" rows="10" placeholder="..." required="required"></textarea>
				    </div>
				    
				    @if(captcha_is_enabled('contact'))
				    @error('captcha')
				      <div class="ui negative message">
				        <strong>{{ $message }}</strong>
				      </div>
				    @enderror

				    @error('g-recaptcha-response')
				      <div class="ui negative message">
				        <strong>{{ $message }}</strong>
				      </div>
				    @enderror

				    <div class="field d-flex justify-content-center">
				      {!! render_captcha() !!}

				      @if(captcha_is('mewebstudio'))
				      <input type="text" name="captcha" value="{{ old('captcha') }}" class="ml-1">
				      @endif
				    </div>
				    @endif

				    <div class="field"><button type="submit" class="ui big yellow button">{{ __('Submit') }}</button></div>
				  </form>
				</div>
			</div>
		</div>

	</div>

@endsection