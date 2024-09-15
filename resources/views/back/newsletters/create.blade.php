@extends('back.master')

@section('title', __('Create and send newsletter'))

@section('additional_head_tags')
<script type="application/javascript" src="/assets/base64.min.js"></script>
<script type="application/javascript" src="/assets/node-html-parser.min.js"></script>

@if(config('app.html_editor') == 'summernote')
<link href="{{ asset_('assets/admin/summernote-lite-0.8.12.css') }}" rel="stylesheet">
<script src="{{ asset_('assets/admin/summernote-lite-0.8.12.js') }}"></script>
@else
<script src="{{ asset_('assets/tinymce_5.9.2/js/jquery.tinymce.min.js') }}"></script>
<script src="{{ asset_('assets/tinymce_5.9.2/js/tinymce.min.js') }}"></script>
@endif

@endsection

@section('content')

<form class="ui large form" method="post" action="{{ route('subscribers.newsletter.send') }}" id="newsletter" spellcheck="false">
	@csrf
	
	<textarea class="d-none" name="newsletter_template"></textarea>

	@if($errors->any())
    @foreach ($errors->all() as $error)
		<div class="ui negative fluid small message circular-corner bold">
			<i class="times icon close"></i>
			{{ $error }}
		</div>
    @endforeach
	@endif

	@if(session('newsletter_sent'))
	<div class="ui positive fluid small bold message circular-corner bold">
		<i class="times icon close"></i>
		{{ session('newsletter_sent') }}
	</div>
	@endif
	
	<div class="fields-wrapper">

		<div class="field">
			<label>{{ __('Subscribers') }}</label>
			<div class="ui floating multiple selection scrolling search fluid dropdown">
				<input type="hidden" name="emails" value="{{ old('emails') }}">
				<div class="text"></div>
				<i class="dropdown icon"></i>
				<div class="menu">
					@foreach($emails as $email)
					<a data-value="{{ $email }}" class="item">{{ $email }}</a>
					@endforeach
				</div>
			</div>
		</div>

		<div class="field">
			<label>{{ __('Subject') }}</label>
			<input type="text" name="subject" required value="{{ old('subject') }}" class="circular-corner">
		</div>

		<div class="field">
			<label>{{ __('Use newsletter template') }}</label>
			<div class="ui floating selection fluid dropdown tool">
				<input type="hidden" name="option">
				<div class="text">...</div>
				<i class="dropdown icon"></i>
				<div class="menu">
					<a data-value="newsletter_1" class="item">{{ __('Newsletter template 1') }}</a>
					<a data-value="newsletter_2" class="item">{{ __('Newsletter template 2') }}</a>
				</div>
			</div>
		</div>

		<div class="field">
			<label>{{ __('Use plain text') }}</label>
			<div class="field rounded-corner">
				<textarea name="newsletter_text" spellcheck="false" cols="30">{{ old('newsletter_text') }}</textarea>
			</div>
		</div>

		<div class="field">
			<label>{{ __('Use HTML editor') }}</label>
			<div class="field rounded-corner">
				<textarea name="newsletter_html" id="html-editor" spellcheck="false" cols="30">{{ old('newsletter_html') }}</textarea>
			</div>
		</div>
	</div>

	<div class="field right aligned mt-1">
		<a href="{{ route('subscribers') }}" class="ui teal right large labeled icon circular button mx-0 left floated">
		  <i class="times icon mx-0"></i>
		  {{ __('Cancel') }}
		</a>

		<button type="submit" name="action" value="send" class="ui pink large labeled icon circular button mx-1-hf"
						onclick="$(this).closest('form').attr('target', '_self')">
		  <i class="save outline icon mx-0"></i>
		  {{ __('Send') }}
		</button>

		<button type="submit" name="action" value="render" class="ui yellow large labeled icon circular button mx-0" title="{{ __('Visualize') }}" onclick="$(this).closest('form').attr('target', '_blank')">
		  <i class="eye icon mx-0"></i>
		  {{ __('Preview') }}
		</button>

		<div class="newsletter-template">
			<i class="close icon"></i>
			<div class="content"></div>
		</div>

		<div class="ui products modal" id="products-modal">
			<div class="content header">
				<div class="title">{{ __('Select items') }}</div>
				<div class="search">
					<div class="ui right icon input">
						<input type="text" name="search" placeholder="{{ __('Enter item name') }}">
						<i class="search link icon"></i>
					</div>
				</div>
			</div>

			<div class="content body">
				<div class="ui three stackable cards">
				</div>
			</div>
			
			<div class="content footer actions">
				<button class="ui yellow rounded-corner button cancel close mx-0">{{ __('Close') }}</button>	
			</div>
		</div>
	</div>
</form>

<script>
	'use strict';
	
	$(function()
	{
		let newsletters = @json($templates);
		let productIndex = null;
		let subscriptions = @json($subscriptions);
		let products = [];
		let selectedTemplate = "";

		$('.newsletter-template i.close').on('click', function()
		{
			for(let k in newsletters)
			{
				if(newsletters[k].selected === 1)
				{
					newsletters[k].html = Base64.encode($('.newsletter-template .content').html());
					break;
				}
			}

			$('.newsletter-template .content').html('')
			$('.newsletter-template').hide()	
		})


		$('.ui.dropdown.tool .item').on('click', function()
		{
			let opt = $(this).data('value').trim();

			if(opt.length && opt !== 'html_editor')
			{
				selectedTemplate = opt;

				newsletters[opt].selected = 1;

				for(let name in newsletters)
				{
					if(name != opt)
					{
						newsletters[name].selected = 0;	
					}
				}
				
				if(newsletters[opt].html.length)
				{
					$('.newsletter-template .content').html(Base64.decode(newsletters[opt].html))
					$('.newsletter-template').show()
				}
				else
				{
					$.get('{{ route('subscribers.newsletter.get_template') }}', {template: opt})
					.done(template =>
					{
						newsletters[opt].html = Base64.encode(template)
						
						$('.newsletter-template .content').html(template);

						$('.newsletter-template').show()

						$('.subscriptions .link').each(function()
						{
							let menuItems = []
							
							for(let k in subscriptions)
							{
								menuItems.push(`
									<div class="item" data-url="${subscriptions[k].url}">${subscriptions[k].name}</div>
								`);
							}

							$(this).append(`
								<div class="menu">
									${menuItems.join('')}
								</div>
							`)
						})
					})
				}
			}
			else if(opt === 'html_editor')
			{

			}
		})



		$(document).on('click', '.newsletter-template .products .item', function(e)
		{
			productIndex = $('.newsletter-template .products .item').index($(this));

			if(!products.length)
			{
				let response = $.ajax({
			    type: "POST",
			    url: '{{ route('products.api') }}',
			    data: {limit: 10000},
			    async: false
				}).responseJSON;


				if(Object.keys(response.products).length)
				{
					products = response.products;
				}
			}

			let items = [];

			for(let item of products)
			{
				items.push(`
				<div class="card fluid" data-name="${item.name}" data-cover="${location.origin}/storage/covers/${item.cover}" data-href="${location.origin}/item/${item.id}/${item.slug}">
					<div class="image"><img src="${location.origin}/storage/covers/${item.cover}"></div>
					<div class="content">${item.name.shorten(50)}</div>
				</div>
				`);
			}

			$('.ui.products.modal .content.body .cards').html('');
			
			$('.ui.products.modal .content.body .cards').html(items.join(''));
			$('.ui.products.modal').modal({center: true, closable: false}).modal('show')

			e.preventDefault();
		})



		$(document).on('click', '#products-modal .card', function()
		{
			try
			{
				let data = $(this).data();
				let item = $($('.newsletter-template .products .item').get(productIndex));

				if(selectedTemplate == 'newsletter_1')
				{
					item.find("div.cover").css("background-image", `url('${data.cover}')`)
				}
				else if(selectedTemplate == 'newsletter_2')
				{
					item.css("background-image", `url('${data.cover}')`)
				}

				item.attr('href', data.href)
				item.find('div.text').text(data.name);

				productIndex = null;

				$('#products-modal').modal('hide');
			}
			catch(err)
			{

			}
		})


		$(document).on('click', '.subscriptions .link', function()
		{
			$(this).toggleClass('active', !$(this).hasClass('active'))

			$('.subscriptions .link').not($(this)).toggleClass('active', false)

			return false;
		})


		$(document).on('click', '.subscriptions .link .item', function()
		{
			$('.subscriptions .link').removeClass('active')

			$(this).closest('.link').closest('.item').find('.subscription-link').attr("href", $(this).data('url'))

			return false;
		})


		$('#products-modal input[name="search"]').on('keyup', function()
		{
			let q = $(this).val().trim();

			if(q.length)
			{
				let re = new RegExp(q, "i");

				$('#products-modal .card').each(function()
				{
					if(!re.test($(this).data('name')))
					{
						$(this).toggleClass('d-none', true)
					}
				})
			}
			else
			{
				$('#products-modal .card').toggleClass('d-none', false)
			}
		})


		$('form#newsletter').on('submit', function(e)
		{
			for(let k in newsletters)
			{
				if(newsletters[k].selected)
				{
					$('textarea[name="newsletter_template"]').val(JSON.stringify(newsletters[k]));

					break;
				}
			}
		})


  	@if(config('app.html_editor') == 'summernote')
  	$('#html-editor').summernote({
	    tabsize: 2,
	    height: 450,
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

		tinymce.init(Object.assign(tinyMceOpts, {selector: '#html-editor'}));
	  @endif
	})
</script>

@endsection