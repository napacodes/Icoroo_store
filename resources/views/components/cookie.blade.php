@if(config('app.cookie.text'))
<div id="cookie">
	<div class="content">
		<div class="icon">
			<img src="/assets/images/cookie-2.svg">
			<span>{{ __('Cookies') }}!</span>
		</div>
		<div class="text">{!! config('app.cookie.text') !!}</div>
	</div>
	<div class="action">
		<button class="ui yellow large button accept">{{ __('I understand') }}</button>
	</div>
</div>
@endif