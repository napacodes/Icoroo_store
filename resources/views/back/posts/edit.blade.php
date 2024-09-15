@extends('back.master')

@section('title', $title)

@section('additional_head_tags')

@if(config('app.html_editor') == 'summernote')
<script src="{{ asset_('assets/admin/summernote-lite-0.8.12.js') }}"></script>
<script src="{{ asset_('assets/wavesurfer.min.js') }}"></script>
@else
<script src="{{ asset_('assets/tinymce_5.9.2/js/jquery.tinymce.min.js') }}"></script>
<script src="{{ asset_('assets/tinymce_5.9.2/js/tinymce.min.js') }}"></script>
@endif

@endsection


@section('content')
<form class="ui large form" method="post" action="{{ route('posts.update', $post->id) }}" enctype="multipart/form-data">
	@csrf

	<div class="field">
		<button class="ui icon labeled large circular button" type="submit">
		  <i class="save outline icon"></i>
		  {{ __('Update') }}
		</button>
		<a class="ui icon labeled large circular button" href="{{ route('posts') }}">
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

	<div class="one column grid" id="post">
		<div class="column">
			<div class="field">
				<label>{{ __('Name') }}</label>
				<input type="text" name="name" placeholder="..." value="{{ old('name', $post->name) }}" autofocus required>
			</div>
			<div class="field">
				<label>{{ __('Cover') }}</label>
				<div class="ui placeholder rounded-corner" onclick="this.children[1].click()">
					<div class="ui image">
						<img loading="lazy" src="{{ asset_("storage/posts/{$post->cover}?v=".time()) }}">
					</div>
					<input type="file" class="d-none" name="cover" accept=".jpg,.jpeg,.png,.gif,.svg">
				</div>
			</div>
			<div class="field">
				<label>{{ __('Category') }}</label>
				<div class="ui selection dropdown floating">
				  <input type="hidden" name="category" value="{{ old('category', $post->category) }}">
				  <i class="dropdown icon"></i>
				  <div class="default text">-</div>
				  <div class="menu">
				  	@foreach($categories as $category)
						<div class="item" data-value="{{ $category->id }}">
							{{ ucfirst($category->name) }}
						</div>
				  	@endforeach
				  </div>
				</div>
				<input class="mt-1" type="text" name="new_category" placeholder="{{ __('Add new category') }} ..." value="{{ old('new_category') }}">
			</div>
			<div class="field">
				<label>{{ __('Short description') }}</label>
				<textarea name="short_description" cols="30" rows="5">{{ old('short_description', $post->short_description) }}</textarea>
			</div>
			<div class="field">
				<label>{{ __('Content') }}</label>
				<textarea name="content" class="html-editor" cols="30" rows="20">{{ old('content', $post->content) }}</textarea>
			</div>
			<div class="field">
				<label>{{ __('Tags') }}</label>
				<input type="text" name="tags" value="{{ old('tags', $post->tags) }}">
			</div>
		</div>
	</div>
</form>

<script type="application/javascript">
	'use strict';
	@if(config('app.html_editor') == 'summernote')
	$('.html-editor').summernote({
    placeholder: '...',
    tabsize: 2,
    height: 350,
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
	  height: 600,
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