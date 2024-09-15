@extends('back.master')

@section('title', __('Edit - :name pack', ['name' => $prepaid_credits->name]))

@section('additional_head_tags')

@if(config('app.html_editor') == 'summernote')
<link href="{{ asset_('assets/admin/summernote-lite-0.8.12.css') }}" rel="stylesheet">
<script src="{{ asset_('assets/admin/summernote-lite-0.8.12.js') }}"></script>
@else
<script src="{{ asset_('assets/tinymce_5.9.2/js/jquery.tinymce.min.js') }}"></script>
<script src="{{ asset_('assets/tinymce_5.9.2/js/tinymce.min.js') }}"></script>
@endif

@endsection


@section('content')
<form class="ui large form" method="post" action="{{ route('prepaid_credits.update', ['id' => $prepaid_credits->id]) }}">
	@csrf

	<div class="field">
		<button class="ui icon labeled large circular button" type="submit">
		  <i class="save outline icon"></i>
		  {{ __('Save') }}
		</button>
		<a class="ui icon labeled large circular button" href="{{ route('prepaid_credits') }}">
			<i class="times icon"></i>
			{{ __('Cancel') }}
		</a>
	</div>
	
	@if($errors->any())
      @foreach ($errors->all() as $error)
         <div class="ui negative fluid small message">
         	<i class="times icon close"></i>
         	{{ $error }}
         </div>
      @endforeach
	@endif

	<div class="ui fluid divider"></div>

	<div class="one column grid" id="prepaid_credits">
		<div class="column">
			<div class="field">
				<label>{{ __('Name') }}</label>
				<input type="text" name="name" placeholder="..." value="{{ old('name', $prepaid_credits->name) }}" autofocus required>
			</div>
			<div class="field">
				<label>{{ __('Amount') }}</label>
				<input type="number" name="amount" placeholder="..." value="{{ old('amount', $prepaid_credits->amount) }}" required>
			</div>
			<div class="field">
				<label>{{ __('Discount on products') }} <sup>({{ __('Percentage') }})</sup></label>
				<input type="number" name="discount" placeholder="..." value="{{ old('discount', $prepaid_credits->discount ?? 0) }}">
			</div>
			<div class="field">
				<label>{{ __('Popular') }}</label>
				<div class="ui selection floating dropdown">
					<input type="hidden" name="popular" value="{{ old('popular', $prepaid_credits->popular) }}">
					<div class="text">...</div>
					<div class="menu">
						<a class="item" data-value="">-</a>
						<a class="item" data-value="1">{{ __('Yes') }}</a>
						<a class="item" data-value="0">{{ __('No') }}</a>
					</div>
				</div>
			</div>
			<div class="field">
				<label>{{ __('Specifications') }}</label>
				<textarea name="specs" class="html-editor" rows="10">{!! old('specs', base64_decode($prepaid_credits->specs)) !!}</textarea>
			</div>
		</div>
	</div>
</form>

<script>
	'use strict';

	@if(config('app.html_editor') == 'summernote')
	$('.html-editor').summernote({
    tabsize: 2,
    height: 200,
    tooltip: false
  });
  @else
	window.tinyMceOpts = {
	  plugins: 'print preview paste importcss searchreplace autolink autosave save directionality code visualblocks visualchars fullscreen image link media template codesample table charmap hr pagebreak nonbreaking anchor toc insertdatetime advlist lists wordcount imagetools textpattern noneditable help charmap quickbars emoticons' /* bbcode */,
	  imagetools_cors_hosts: ['picsum.photos'],
	  menubar: 'file edit view insert format tools table help',
	  toolbar: 'undo redo | bold italic underline strikethrough | fontselect fontsizeselect formatselect | alignleft aligncenter alignright alignjustify | outdent indent |  numlist bullist | forecolor backcolor removeformat | pagebreak | charmap emoticons | fullscreen  preview save print | insertfile image media template link anchor codesample | ltr rtl',
	  toolbar_sticky: true,
	  autosave_ask_before_unload: true,
	  autosave_interval: '30s',
	  autosave_prefix: '{path}{query}-{id}-',
	  autosave_restore_when_empty: false,
	  autosave_retention: '2m',
	  image_advtab: true,
	  link_list: [
	    { title: 'My page 1', value: 'https://www.tiny.cloud' },
	    { title: 'My page 2', value: 'http://www.moxiecode.com' }
	  ],
	  image_list: [
	    { title: 'My page 1', value: 'https://www.tiny.cloud' },
	    { title: 'My page 2', value: 'http://www.moxiecode.com' }
	  ],
	  image_class_list: [
	    { title: 'None', value: '' },
	    { title: 'Some class', value: 'class-name' }
	  ],
	  importcss_append: true,
	  file_picker_callback: function (callback, value, meta) {
	    /* Provide file and text for the link dialog */
	    if (meta.filetype === 'file') {
	      callback('https://www.google.com/logos/google.jpg', { text: 'My text' });
	    }

	    /* Provide image and alt text for the image dialog */
	    if (meta.filetype === 'image') {
	      callback('https://www.google.com/logos/google.jpg', { alt: 'My alt text' });
	    }

	    /* Provide alternative source and posted for the media dialog */
	    if (meta.filetype === 'media') {
	      callback('movie.mp4', { source2: 'alt.ogg', poster: 'https://www.google.com/logos/google.jpg' });
	    }
	  },
	  templates: [
	        { title: 'New Table', description: 'creates a new table', content: '<div class="mceTmpl"><table width="98%%"  border="0" cellspacing="0" cellpadding="0"><tr><th scope="col"> </th><th scope="col"> </th></tr><tr><td> </td><td> </td></tr></table></div>' },
	    { title: 'Starting my story', description: 'A cure for writers block', content: 'Once upon a time...' },
	    { title: 'New list with dates', description: 'New List with dates', content: '<div class="mceTmpl"><span class="cdate">cdate</span><br /><span class="mdate">mdate</span><h2>My List</h2><ul><li></li><li></li></ul></div>' }
	  ],
	  template_cdate_format: '[Date Created (CDATE): %m/%d/%Y : %H:%M:%S]',
	  template_mdate_format: '[Date Modified (MDATE): %m/%d/%Y : %H:%M:%S]',
	  height: 200,
	  image_caption: true,
	  quickbars_selection_toolbar: 'bold italic | quicklink h2 h3 blockquote quickimage quicktable',
	  noneditable_noneditable_class: 'mceNonEditable',
	  toolbar_mode: 'sliding',
	  contextmenu: 'link image imagetools table',
	  skin: 'oxide',
	  content_css: 'default',
	  content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }'
	};

	@if(config('app.html_editor') == 'tinymce_bbcode')
	{
		tinyMceOpts.plugins += ' bbcode';
	}
	@endif

	tinymce.init(Object.assign(tinyMceOpts, {selector: '.html-editor'}));
  @endif
</script>

@endsection