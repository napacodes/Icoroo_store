@extends('back.master')

@section('title', __('Create product'))

@section('additional_head_tags')
@if(config('app.html_editor') == 'summernote')
<link href="{{ asset_('assets/admin/summernote-lite-0.8.12.css') }}" rel="stylesheet">
<script src="{{ asset_('assets/admin/summernote-lite-0.8.12.js') }}"></script>
@else
<script src="{{ asset_('assets/tinymce_5.9.2/js/jquery.tinymce.min.js') }}"></script>
<script src="{{ asset_('assets/tinymce_5.9.2/js/tinymce.min.js') }}"></script>
@endif

<script>
	'use strict';

	const mimetypes = @json(config("mimetypes"));
</script>
@endsection


@section('content')
<div id="product" vhidden>
	<form class="ui large main form" method="post" autocomplete="off" enctype="multipart/form-data" action="{{ route('products.store') }}">
		<div class="field">
			<input type="submit" id="submit" class="d-none">
			<button class="ui icon labeled circular large button" :class="{disabled: anyInputOff()}" type="submit" id="save">
			  <i class="save outline icon"></i>
			  {{ __('Save') }}
			</button>
			<a class="ui icon labeled circular large button" :class="{disabled: anyInputOff()}" href="{{ route('products') }}">
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

		<div class="ui one column grid">
			<div class="column tabs">
				<div class="ui top attached tabular menu">
			    <a class="active item" data-tab="overview">{{ __('Overview') }}</a>
				  <a class="item" data-tab="hidden-content">{{ __('Hidden content') }}</a>
				  <a class="item" data-tab="pricing">{{ __('Pricing') }}</a>
				  <a class="item" data-tab="seo">{{ __('SEO') }}</a>
				  <a class="item" data-tab="table-of-contents">{{ __('Table of contents') }}</a>
				  <a class="item" data-tab="faq">{{ __('FAQ') }}</a>
				  <a class="item" data-tab="additional-fields">{{ __('Additional fields') }}</a>
				  <a class="item" data-tab="reviews">{{ __('Reviews') }}<sup class="ml-1-hf">({{ __('Fake') }})</sup></a>
				  <a class="item" data-tab="comments">{{ __('Comments') }}<sup class="ml-1-hf">({{ __('Fake') }})</sup></a>
			  </div>

			  <div class="ui tab segment active" data-tab="overview">
			  	<div class="field">
						<label>{{ __('Name') }}</label>
						<input type="text" name="name" placeholder="..." value="{{ old('name') }}" autofocus required>
					</div>

					<div class="field">
						<label>{{ __('Permalink') }} <i class="exclmation icon mr-0" title="{{ __('You must fill in [Permalink url identifer] field in general settings to enable this field.') }}"></i></label>
						<div class="ui labeled input">
							<div class="ui basic label">{{ config('app.url').'/'.config('app.permalink_url_identifer') }}/</div>
						  <input type="text" name="permalink" {{ !config('app.permalink_url_identifer') ? "disabled" : "" }} placeholder="..." value="{{ old('permalink') }}">
						</div>
					</div>

					<div class="field">
						<label>{{ __('Affiliate link') }}</label>
						<input type="text" name="affiliate_link" placeholder="..." value="{{ old('affiliate_link') }}">
					</div>

					<div class="field">
						<label>{{ __('Short description') }}</label>
						<textarea name="short_description" cols="30" rows="5">{{ old('short_description') }}</textarea>
					</div>

					<div class="field">
						<label>{{ __('Category') }}</label>
						<div class="ui selection floating dropdown">
						  <input type="hidden" name="category" value="{{ old('category') }}">
						  <i class="dropdown icon"></i>
						  <div class="default text">-</div>
						  <div class="menu">
						  	@foreach($category_parents as $category_parent)
								<div class="item" data-value="{{ $category_parent->id }}">
									{{ ucfirst($category_parent->name) }}
								</div>
						  	@endforeach
						  </div>
						</div>
					</div>

					<div class="field">
						<label>{{ __('Subcategories') }}</label>
						<div class="ui multiple selection floating dropdown" id="subcategories">
							<input type="hidden" name="subcategories" value="{{ old('subcategories') }}">
							<i class="dropdown icon"></i>
							<div class="default text">{{ __('Select subcategory') }}</div>
							<div class="menu"></div>
						</div>
					</div>

					<div class="field">
						<label>{{ __('Full description') }} <sup>({{ __('Overview') }})</sup></label>
						<textarea name="overview" class="html-editor">{!! old('overview') !!}</textarea>
					</div>

					<div class="field">
						<label>{{ __('Included files') }}</label>
						<input type="text" name="included_files" value="{{ old('included_files') }}" placeholder="...">
					</div>

					<div class="field">
						<label>{{ __('Version') }}</label>
						<input type="text" name="version" value="{{ old('version') }}" placeholder="...">
					</div>

					<div class="ui hidden divider"></div>

					<div class="field">
						<label>{{ __('Release date') }}</label>
						<input type="date" name="release_date" value="{{ old('release_date') }}" placeholder="...">
					</div>

					<div class="ui hidden divider"></div>

					<div class="field">
						<label>{{ __('Latest update') }}</label>
						<input type="date" name="last_update" value="{{ old('last_update') }}" placeholder="...">
					</div>
					
					<div class="field">
						<label>{{ __('Tags') }} <sup>({{ __('Optional') }})</sup></label>
						<input type="text" name="tags" value="{{ old('tags') }}" placeholder="...">
					</div>

					<div class="field">
						<label>{{ __('Preview link') }} <sup>({{ __('Optional') }})</sup></label>
						<input type="text" name="preview_url" class="d-block" placeholder="https://..." value="{{ old('preview_url') }}">
					</div>

					<div class="ui hidden divider"></div>

					<div class="field">
						<label>{{ __('Quantity in stock') }}</label>
						<input type="number" name="stock" value="{{ old('stock') }}">
						<small><i class="circular exclamation small red icon"></i>{{ __('Leave empty if not applicable.') }}</small>
					</div>

					<div class="ui hidden divider"></div>

					<div class="field">
						<label>{{ __('Enable license') }}</label>
						<div class="ui floating selection dropdown">
							<input type="hidden" name="enable_license" value="{{ old('enable_license', '0') }}">
							<div class="text">...</div>
							<div class="menu">
								<a class="item" data-value="1">{{ __('Yes') }}</a>
								<a class="item" data-value="0">{{ __('No') }}</a>
							</div>
						</div>
					</div>

					<div class="field">
						<label>{{ __('Fake sales') }}</label>
						<input type="number" name="fake_sales" value="{{ old('fake_sales') }}">
					</div>

					<div class="field">
						<label>{{ __('Available via subscription only') }}</label>
						<div class="ui floating selection dropdown">
							<input type="hidden" name="for_subscriptions" value="{{ old('for_subscriptions', '0') }}">
							<div class="text">...</div>
							<div class="menu">
								<a class="item" data-value="1">{{ __('Yes') }}</a>
								<a class="item" data-value="0">{{ __('No') }}</a>
							</div>
						</div>
					</div>

					@if(config('app.products_by_country_city'))
					<div class="field">
						<label>{{ __('Country') }}</label>
						<div class="ui floating search selection dropdown countries">
							<input type="hidden" name="country_city[country]" value="{{ old('country_city.country') }}">
							<div class="text">...</div>
							<div class="menu">
								<a class="item" data-value=""></a>
								@foreach(config('app.countries_cities') as $country => $cities)
								<a class="item" data-value="{{ $country }}">{{ $country }}</a>
								@endforeach
							</div>
						</div>
					</div>

					<div class="field">
						<label>{{ __('City') }}</label>
						<div class="ui floating search selection dropdown cities">
							<input type="hidden" name="country_city[city]" value="{{ old('country_city.city') }}">
							<div class="text">...</div>
							<div class="menu"></div>
						</div>
					</div>
					@endif

					<div class="files">
						<p class="m-0 bold">{{ __('Files') }}</p>

						<div class="ui four stackable doubling cards">
							<div class="fluid card">
								<div class="content">
									<div class="header">{{ __('Main file') }} <sup>({{ __('Optional') }})</sup></div>
								</div>
								<div class="content flex-direction-column">
									<input type="hidden" name="file_host" :value="selectedDrive" class="d-none">
									<input type="hidden" name="file_name" :value="fileId" class="d-none">
									<input type="hidden" name="file_extension" :value="fileExtension" class="d-none">

									<div v-if="oldUploads.download.length" class="ui fluid large pink label">
										{{ $download }}
										<i class="close icon ml-auto mr-0" @click="deleteExistingFile('{{ "storage/app/downloads/{$download}" }}', 'download')"></i>
									</div>

									<div class="w-100" v-else-if="fileId">
										<div class="ui fluid large pink label">
											@{{ fileId }}
											<i class="close icon ml-auto mr-0" @click="removeSelectedFile"></i>	
										</div>	
									</div>

									<div class="w-100" v-else>
										<div v-if="hasProgress('download')">
											<div v-if="uploadInProgress('download')">
												<progress :value="ajaxRequests.download.progress" max="100"></progress>
												<a  v-if="!finishedUploading('download')" class="ui mini red button circular mb-1-hf" @click="abortUpload('download')">{{ __('Abort upload') }}</a>
											</div>

											<div class="ui fluid large pink label mb-1" v-else>
												@{{ ajaxRequests.download.file_name }}
												<i class="close icon ml-auto mr-0" @click="removeUploadedFile('download')"></i>
											</div>
										</div>

										<div class="ui floating circular scrolling fluid dropdown large blue basic button mx-0 files">
											<div class="text d-block center aligned">{{ __('Browse') }}</div>
											<div class="menu">
												<a class="item" @click="browserMainFile('local')">{{ __('Local device') }}</a>

												@if(config('filehosts.amazon_s3.enabled'))
												<div class="item" @click="browserMainFile('amazon_s3')">{{ __('Amazon S3') }}</div>
												@endif

												@if(config('filehosts.google_drive.enabled'))
												<div class="item" @click="browserMainFile('google')">{{ __('Google Drive') }}</div>
												@endif

												@if(config('filehosts.wasabi.enabled'))
												<div class="item" @click="browserMainFile('wasabi')">{{ __('Wasabi') }}</div>
												@endif

												@if(config('filehosts.google_cloud_storage.enabled'))
												<div class="item" @click="browserMainFile('gcs')">{{ __('Google cloud storage') }}</div>
												@endif

												@if(config('filehosts.dropbox.enabled'))
												<div class="item" @click="browserMainFile('dropbox')">{{ __('DropBox') }}</div>
												@endif

												@if(config('filehosts.yandex.enabled'))
												<div class="item" @click="browserMainFile('yandex')">{{ __('Yandex') }}</div>
												@endif
											</div>
										</div>
									</div>

									<div class="w-100">
										@if(config('app.enable_upload_links'))
										<input type="url" name="main_file_upload_link" :class="{disabled: inputIsOff('download')}" placeholder="{{ __('Upload link') }}" value="{{ old('main_file_upload_link') }}"  @change="setDefaultDrive" class="mt-1">
										@endif
										
										<input type="url" name="main_file_download_link" placeholder="{{ __('Download link') }}" value="{{ old('main_file_download_link') }}" @change="setDefaultDrive" :class="{disabled: inputIsOff('download')}" class="mt-1 {{ old('main_file_download_link') ? 'active' : '' }}">
									</div>
								</div>
							</div>

							<div class="fluid card">
								<div class="content">
									<div class="header">{{ __('Cover') }} <sup>({{ __('Required') }})</sup></div>
								</div>
								<div class="content">
									<div v-if="oldUploads.cover.length">
										<div class="image position-relative">
									  	<i class="close circular icon ml-auto link mr-0" @click="deleteExistingFile('{{ "public/storage/covers/{$cover}" }}', 'cover')"></i>
									    @if($cover)
											<img loading="lazy" src="{{ asset_("storage/covers/{$cover}") }}">
											@endif
									  </div>
									</div>
									<div v-else>
										<div v-if="hasProgress('cover')">
											<div v-if="uploadInProgress('cover')">
												<progress :value="ajaxRequests.cover.progress" max="100"></progress>
												<a  v-if="!finishedUploading('cover')" class="ui mini red button circular mb-1-hf" @click="abortUpload('cover')">{{ __('Abort upload') }}</a>
											</div>

											<div class="ui fluid large pink label mb-1" v-else>
												@{{ ajaxRequests.cover.file_name }}
												<i class="close icon ml-auto mr-0" @click="removeUploadedFile('cover')"></i>
											</div>
										</div>
										<button class="ui basic large circular blue fluid button" type="button" :class="{disabled: inputIsOff('cover')}" @click="selectFile('cover')">{{ __('Browse') }}</button>
									</div>
								</div>
							</div>

							<div class="fluid card">
								<div class="content">
									<div class="header">{{ __('Screenshots') }} <sup>({{ __('Optional') }})</sup></div>
								</div>
								<div class="content">
									<div v-if="oldUploads.screenshots.length">
										<div class="ui fluid large pink label">
											{{ $screenshots }}
											<i class="close icon ml-auto mr-0" @click="deleteExistingFile('{{ "public/storage/covers/{$screenshots}" }}', 'screenshots')"></i>
										</div>	
									</div>
									<div class="w-100" v-else>
										<div v-if="hasProgress('screenshots')">
											<div v-if="uploadInProgress('screenshots')">
												<progress :value="ajaxRequests.screenshots.progress" max="100"></progress>
												<a  v-if="!finishedUploading('screenshots')" class="ui mini red button circular mb-1-hf" @click="abortUpload('screenshots')">{{ __('Abort upload') }}</a>
											</div>

											<div class="ui fluid large pink label mb-1" v-else>
												@{{ ajaxRequests.screenshots.file_name }}
												<i class="close icon ml-auto mr-0" @click="removeUploadedFile('screenshots')"></i>
											</div>
										</div>
										<button class="ui basic large circular blue fluid button" type="button" :class="{disabled: inputIsOff('screenshots')}" @click="selectFile('screenshots')">{{ __('Browse') }}</button>
									</div>
								</div>
							</div>

							<div class="fluid card">
								<div class="content">
									<div class="header">{{ __('Preview') }} <sup>({{ __('Optional') }})</sup></div>
								</div>

								<div class="content flex-direction-column">
									<div class="w-100" v-if="oldUploads.preview.length">
										<div class="ui fluid large pink label">
											{{ $preview }}
											<i class="close icon ml-auto mr-0" @click="deleteExistingFile('{{ "public/storage/previews/{$preview}" }}', 'preview')"></i>
										</div>	
									</div>
									<div class="w-100" v-else>
										<div v-if="hasProgress('preview')">
											<div v-if="uploadInProgress('preview')">
												<progress :value="ajaxRequests.preview.progress" max="100"></progress>
												<a  v-if="!finishedUploading('preview')" class="ui mini red button circular mb-1-hf" @click="abortUpload('preview')">{{ __('Abort upload') }}</a>
											</div>

											<div class="ui fluid large pink label mb-1" v-else>
												@{{ ajaxRequests.preview.file_name }}
												<i class="close icon ml-auto mr-0" @click="removeUploadedFile('preview')"></i>
											</div>
										</div>

										<a class="ui circular fluid preview large blue basic button mx-0" :class="{disabled: inputIsOff('preview')}" @click="selectFile('preview')">{{ __('Browse') }}</a>
									</div>

									<div class="w-100 mt-1">
										@if(config('app.enable_upload_links'))
										<input type="url" name="preview_upload_link" :class="{disabled: inputIsOff('preview')}" placeholder="{{ __('Upload link') }}" value="{{ old('preview_upload_link') }}" value="{{ old('preview_upload_link') }}" class="mt-1">
										@endif

										<div class="ui left labeled input preview-type fluid">
										  <div class="ui dropdown basic files button label">
										  	<input type="hidden" name="preview_type" value="{{ old('preview_type', 'other') }}">
										    <div class="text">{{ __('Type') }}</div>
										    <div class="menu">
										      <div class="item" data-value="audio">{{ __('Audio') }}</div>
													<div class="item" data-value="video">{{ __('Video') }}</div>
													<div class="item" data-value="archive">{{ __('Archive') }}</div>
													<div class="item" data-value="document">{{ __('Document') }}</div>
													<div class="item" data-value="other">{{ __('Other') }}</div>
										    </div>
										  </div>
										  <input type="url" name="preview_direct_link" :class="{disabled: inputIsOff('preview')}" placeholder="{{ __('Direct link') }}" value="{{ old('preview_direct_link') }}" class="{{ old('preview_direct_link') ? 'active' : '' }}">
										  <input type="hidden" name="preview_extension" v-model="previewExtension">
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
			  </div>

			  <div class="ui tab segment" data-tab="hidden-content">
					<textarea name="hidden_content" class="html-editor">{{ old('hidden_content') }}</textarea>
			  </div>

			  <div class="ui tab segment" data-tab="pricing">
			  	<div class="table wrapper licenses">
				  	<table class="ui basic table unstackable w-100">
					  	@foreach(config('licenses', []) as $license)
					  	<tr>
					  		<td class="three columns wide"><strong>{{ __($license->name) }}</strong></td>
					  		<td>
					  			<input type="number" name="license[price][{{ $license->id }}]" step="0.01" placeholder="{{ __('Default price') }}" value="{{ old("license.price[$license->id]") }}">
					  		</td>
					  		<td>
					  			<input type="number" name="license[promo_price][{{ $license->id }}]" step="0.01" placeholder="{{ __('Promo price') }}" value="{{ old("license.promo_price[$license->id]") }}">
					  		</td>
					  	</tr>
					  	@endforeach
					  </table>
				  </div>
			  	<div class="table wrapper free">
				  	<table class="ui basic table unstackable w-100">
					  	<tr>
					  		<td class="three columns wide"><strong>{{ __('Minimum price') }} <sup>({{ __('Optional') }})</sup></strong></td>
					  		<td><input type="number" step="0.0001" name="minimum_price" value="{{ old('minimum_price') }}"></td>
					  	</tr>
					  </table>
				  </div>

			  	<div class="table wrapper free">
				  	<table class="ui basic table unstackable w-100">
					  	<tr>
					  		<td class="three columns wide"><strong>{{ __('Free') }} <sup>({{ __('Optional') }})</sup></strong></td>
					  		<td><input type="datetime-local" name="free[from]" value="{{ format_date(old('free.from'), 'Y-m-d\TH:i') }}" placeholder="{{ __('From') }}"></td>
					  		<td><input type="datetime-local" name="free[to]" value="{{ format_date(old('free.from'), 'Y-m-d\TH:i') }}" placeholder="{{ __('To') }}"></td>
					  	</tr>
					  </table>
				  </div>

				  <div class="table wrapper promo_price">
				  	<table class="ui basic table unstackable w-100">
					  	<tr>
					  		<td class="three columns wide"><strong>{{ __('Promotional price') }} <sup>({{ __('Optional') }})</sup></strong></td>
					  		<td><input type="datetime-local" name="promotional_price_time[from]" value="{{ format_date(old('promotional_price_time.from'), 'Y-m-d\TH:i') }}" placeholder="{{ __('From') }}"></td>
					  		<td><input type="datetime-local" name="promotional_price_time[to]" value="{{ format_date(old('promotional_price_time.to'), 'Y-m-d\TH:i') }}" placeholder="{{ __('To') }}"></td>
					  	</tr>
					  </table>
				  </div>

				  <div class="ui fluid card mt-1">
						<div class="content"><div class="header left aligned w-100">{{ __('Group buy') }}</div></div>
						<div class="content d-block">
							<div class="field">
								<label>{{ __(' price') }}</label>
								<input type="number" step="0.000000005" name="group_buy_price" value="{{ old('group_buy_price') }}">
							</div>
							<div class="field">
								<label>{{ __('Min buyers') }}</label>
								<input type="number" name="group_buy_min_buyers" value="{{ old('group_buy_min_buyers') }}">
							</div>
							<div class="field">
								<label>{{ __(' Expiry') }}</label>
								<input type="datetime-local" name="group_buy_expiry" value="{{ format_date(old('group_buy_expiry'), 'Y-m-d\TH:i') }}">
							</div>
							<small>{{ __('Note') }} : {{ __('Group buy applies to regular license by default') }}</small>
						</div>
					</div>
			  </div>

			  <div class="ui tab segment" data-tab="seo">
					<div class="field">
						<label>{{ __('Title') }}</label>
						<input type="text" name="meta_tags[title]" value="{{ old('meta_tags.title') }}">
					</div>
					<div class="field">
						<label>{{ __('Description') }}</label>
						<input type="text" name="meta_tags[description]" value="{{ old('meta_tags.description') }}">
					</div>
					<div class="field">
						<label>{{ __('Keywords') }}</label>
						<input type="text" name="meta_tags[keywords]" value="{{ old('meta_tags.keywords') }}">
					</div>
			  </div>

			  <div class="ui tab segment" data-tab="table-of-contents">
			    <table class="ui celled unstackable single line table" 
						 		   data-dict='{"Header": "{{ __('Header') }}", "Type": "{{ __('Type') }}", "Subheader": "{{ __('Subheader') }}", "Sub-Subheader": "{{ __('Sub-Subheader') }}", "Add": "{{ __('Add') }}", "Remove": "{{ __('Remove') }}"}'>
						<thead>
							<tr>
								<th class="left aligned">{{ __('Type') }}</th>
								<th class="left aligned">{{ __('Text') }}</th>
								<th class="center aligned">{{ __('Action') }}</th>
							</tr>
						</thead>

						<tbody>
							@if(old('text_type'))
							
							@foreach(old('text_type') as $key => $text_type)
							<tr>
								<td>
									<div class="ui floating circular fluid dropdown large basic button mx-0">
										<input type="hidden" name="text_type[{{ $key }}]" value="{{ $text_type }}" class="toc-type">
										<span class="default text">{{ __('Type') }}</span>
										<i class="dropdown icon"></i>
										<div class="menu">
											<a class="item" data-value="header">{{ __('Header') }}</a>
											<a class="item" data-value="subheader">{{ __('Subheader') }}</a>
											<a class="item" data-value="subsubheader">{{ __('Sub-Subheader') }}</a>
										</div>
									</div>
								</td>
								<td class="ten column wide right aligned">
									<input type="text" name="text[{{ $key }}]" placeholder="..." value="{{ old('text',)[$key] ?? '' }}" class="toc-text">
								</td>
								<td class="two column wide center aligned actions">
									<i class="times grey circle big icon link" data-action="remove" title="{{ __('Remove') }}"></i>
									<i class="plus blue circle big icon link mx-0" data-action="add" title="{{ __('Add') }}"></i>
								</td>
							</tr>
							@endforeach

							@else

							<tr>
								<td>
									<div class="ui floating circular fluid dropdown large basic button mx-0">
										<input type="hidden" name="text_type[0]" class="toc-type">
										<span class="default text">{{ __('Type') }}</span>
										<i class="dropdown icon"></i>
										<div class="menu">
											<a class="item" data-value="header">{{ __('Header') }}</a>
											<a class="item" data-value="subheader">{{ __('Subheader') }}</a>
											<a class="item" data-value="subsubheader">{{ __('Sub-Subheader') }}</a>
										</div>
									</div>
								</td>
								<td class="ten column wide right aligned">
									<input type="text" name="text[0]" placeholder="..." class="toc-text">
								</td>
								<td class="two column wide center aligned actions">
									<i class="times grey circle big icon link" data-action="remove" title="{{ __('Remove') }}"></i>
									<i class="plus blue circle big icon link mx-0" data-action="add" title="{{ __('Add') }}"></i>
								</td>
							</tr>

							@endif
						</tbody>
					</table>
			  </div>

			  <div class="ui tab segment" data-tab="faq" data-dict='{"Question": "{{ __('Question') }}", "Answer": "{{ __('Answer') }}", "Remove": "{{ __('Remove') }}", "Add": "{{ __('Add') }}"}'>
						@if(old('question') && old('answer'))

							@foreach(old('question') ?? [] as $k => $qa)
								<div class="ui segment">
									<div class="field">
										<label>{{ __('Question') }}</label>
										<input type="text" name="question[{{ $k }}]" class="faq-question" placeholder="..." value="{{ $qa }}">
									</div>
									<div class="field">
										<label>{{ __('Answer') }}</label>
										<textarea name="answer[{{ $k }}]" cols="30" rows="3" class="faq-answer" placeholder="...">{{ old('answer')[$k] ?? '' }}</textarea>
									</div>
									<div class="actions right aligned">
										<i class="times grey circle big icon link" data-action="remove" title="{{ __('Remove') }}"></i>
										<i class="plus blue circle big icon link mx-0" data-action="add" title="{{ __('Add') }}"></i>
									</div>
								</div>
							@endforeach

						@else

						<div class="ui segment">
							<div class="field">
								<label>{{ __('Question') }}</label>
								<input type="text" name="question[0]" class="faq-question" placeholder="...">
							</div>
							<div class="field">
								<label>{{ __('Answer') }}</label>
								<textarea name="answer[0]" cols="30" rows="3" class="faq-answer" placeholder="..."></textarea>
							</div>
							<div class="actions right aligned">
								<i class="times grey circle big icon link" data-action="remove" title="{{ __('Remove') }}"></i>
								<i class="plus blue circle big icon link mx-0" data-action="add" title="{{ __('Add') }}"></i>
							</div>
						</div>

						@endif
				</div>

				<div class="ui tab segment" data-tab="additional-fields" data-dict='{"Name": "{{ __('Name') }}", "Value": "{{ __('Value') }}", "Remove": "{{ __('Remove') }}", "Add": "{{ __('Add') }}"}'>
					<div class="field">
						<label>{{ __('Label') }}</label>
						<input type="text" name="label" value="{{ old('label') }}">
					</div>

					<div class="field">
						<label>{{ __('BPM') }}</label>
						<input type="text" name="bpm" value="{{ old('bpm') }}">
					</div>

					<div class="field">
						<label>{{ __('Bit Rate') }}</label>
						<input type="number" name="bit_rate" value="{{ old('bit_rate') }}">
					</div>

					<div class="field">
						<label>{{ __('Pages') }}</label>
						<input type="number" name="pages" value="{{ old('pages') }}">
					</div>

					<div class="field">
						<label>{{ __('Language') }}</label>
						<input type="text" name="language" value="{{ old('language') }}">
					</div>

					<div class="field">
						<label>{{ __('Formats') }}</label>
						<input type="text" name="formats" value="{{ old('formats') }}">
					</div>

					<div class="field">
						<label>{{ __('Words') }}</label>
						<input type="text" name="words" value="{{ old('words') }}">
					</div>

					<div class="field">
						<label>{{ __('Tools used') }} <i class="exclamation circle icon" title="languages, libraries, frameworks..."></i></label>
						<input type="text" name="software" value="{{ old('software') }}" placeholder="...">
					</div>
					
					<div class="field">
						<label>{{ __('Database used') }} <i class="exclamation circle icon" title="MongoDB, MySQL, SQLite..."></i></label>
						<input type="text" name="database" value="{{ old('database') }}" placeholder="...">
					</div>

					<div class="ui hidden divider"></div>

					<div class="field">
						<label>{{ __('Compatible browsers') }}</label>
						<input type="text" name="compatible_browsers" value="{{ old('compatible_browsers') }}" placeholder="...">
					</div>

					<div class="ui hidden divider"></div>
					
					<div class="field">
						<label>{{ __('Compatible OS') }}</label>
						<input type="text" name="compatible_os" value="{{ old('compatible_os') }}" placeholder="...">
					</div>
						
					<div class="field">
						<label>{{ __('High resolution') }}</label>
						<div class="ui selection floating dropdown">
							<input type="hidden" name="high_resolution" value="{{ old('high_resolution') }}">
							<div class="text">...</div>
							<div class="menu">
								<a class="item" data-value="">-</a>
								<a class="item" data-value="1">{{ __('Yes') }}</a>
								<a class="item" data-value="0">{{ __('No') }}</a>
							</div>
						</div>
					</div>

					<div class="field">
						<label>{{ __('Authors') }}</label>
						<input type="text" name="authors" value="{{ old('authors') }}">
					</div>

					@if(old('_name_') && old('_value_'))
						@foreach(old('_name_') ?? [] as $k => $na)
							<div class="ui segment">
								<div class="two fields">
									<div class="three columns wide field">
										<label>{{ __('Name') }}</label>
										<input type="text" name="_name_[{{ $k }}]" class="addtional-info-name" placeholder="..." value="{{ $na }}">
									</div>
									<div class="thirteen columns wide field">
										<label>{{ __('Value') }}</label>
										<input type="text" name="_value_[{{ $k }}]" class="addtional-info-value" placeholder="..." value="{{ old('value')[$k] ?? '' }}">
									</div>
								</div>
								<div class="actions right aligned">
									<i class="times grey circle big icon link" data-action="remove" title="{{ __('Remove') }}"></i>
									<i class="plus blue circle big icon link mx-0" data-action="add" title="{{ __('Add') }}"></i>
								</div>
							</div>
						@endforeach

					@else

						<div class="ui segment">
							<div class="two fields">
								<div class="three columns wide field">
									<label>{{ __('Name') }}</label>
									<input type="text" name="_name_[0]" class="addtional-info-name" placeholder="...">
								</div>
								<div class="thirteen columns wide field">
									<label>{{ __('Value') }}</label>
									<input type="text" name="_value_[0]" class="addtional-info-value" placeholder="...">
								</div>
							</div>
							<div class="actions right aligned">
								<i class="times grey circle big icon link" data-action="remove" title="{{ __('Remove') }}"></i>
								<i class="plus blue circle big icon link mx-0" data-action="add" title="{{ __('Add') }}"></i>
							</div>
						</div>

						@endif
				</div>

				<div class="ui tab segment" data-tab="reviews">
					<table class="ui celled unstackable single line table" data-dict='{"Add": "{{ __('Add') }}", "Remove": "{{ __('Remove') }}", "reset": "{{ __('Reset') }}"}'>
						<thead>
							<tr>
								<th class="left aligned">{{ __('Username') }}</th>
								<th class="left aligned">{{ __('Created_at') }}</th>
								<th class="left aligned">{{ __('Review') }}</th>
								<th class="center aligned">{{ __('Rating') }}</th>
								<th class="center aligned">&nbsp;</th>
							</tr>
						</thead>

						<tbody>
							@if(count(old('fake_reviews', [])))
							@foreach(old('fake_reviews') as $k => $fake_reviews)
							<tr>
								<td>
									<input type="text" class="reviews-username" name="fake_reviews[username][0]" value="{{ old("fake_reviews.username.{$k}") }}">
								</td>
								<td class="one colum wide">
									<input type="datetime-local" class="reviews-created_at" name="fake_reviews[created_at][0]" value="{{ old("fake_reviews.created_at.{$k}") }}">
								</td>
								<td><textarea class="reviews-review" name="fake_reviews[review][0]" rows="2">{{ old("fake_reviews.review.{$k}") }}</textarea></td>
								<td class="one colum wide center aligned">
									<input type="hidden" class="reviews-rating" name="fake_reviews[rating][0]" value="{{ old("fake_reviews.rating.{$k}") }}">
									<div class="ui star review large huge rating" data-max-rating="5" data-rating="{{ old("fake_reviews.rating.{$k}", 0) }}"></div>
								</td>
								<td class="one column wide center aligned actions">
									<div class="mb-1-hf">
										<button type="button" class="ui small button mr-0 circular icon fluid" data-action="reset">
											{{ __('Reset') }}
										</button>
									</div>
									<div>
										<i class="times grey circle big icon link" data-action="remove" title="{{ __('Remove') }}"></i>
										<i class="plus blue circle big icon link mx-0" data-action="add" title="{{ __('Add') }}"></i>
									</div>
								</td>
							</tr>
							@endforeach
							@else
							<tr>
								<td><input type="text" class="reviews-username" name="fake_reviews[username][0]"></td>
								<td class="one colum wide"><input type="datetime-local" class="reviews-created_at" name="fake_reviews[created_at][0]"></td>
								<td><textarea class="reviews-review" name="fake_reviews[review][0]" rows="2"></textarea></td>
								<td class="one colum wide center aligned">
									<input type="hidden" class="reviews-rating" name="fake_reviews[rating][0]">
									<div class="ui star review large huge rating" data-max-rating="5"></div>
								</td>
								<td class="one column wide center aligned actions">
									<div class="mb-1-hf">
										<button type="button" class="ui small button mr-0 circular icon fluid" data-action="reset">
											{{ __('Reset') }}
										</button>
									</div>
									<div>
										<i class="times grey circle big icon link" data-action="remove" title="{{ __('Remove') }}"></i>
										<i class="plus blue circle big icon link mx-0" data-action="add" title="{{ __('Add') }}"></i>
									</div>
								</td>
							</tr>
							@endif
						</tbody>
					</table>
				</div>

				<div class="ui tab segment" data-tab="comments">
					<table class="ui celled unstackable single line table" data-dict='{"Add": "{{ __('Add') }}", "Remove": "{{ __('Remove') }}", "reset": "{{ __('Reset') }}"}'>
						<thead>
							<tr>
								<th class="left aligned">{{ __('Username') }}</th>
								<th class="left aligned">{{ __('Created_at') }}</th>
								<th class="left aligned">{{ __('Comment') }}</th>
								<th class="center aligned">&nbsp;</th>
							</tr>
						</thead>

						<tbody>
							@if(count(old('fake_comments', [])))
							@foreach(old('fake_comments') as $k => $fake_comment)
							<tr>
								<td>
									<input type="text" class="comments-username" name="fake_comments[username][0]" value="{{ old("fake_comments.username.{$k}") }}">
								</td>
								<td class="one colum wide">
									<input type="datetime-local" class="comments-created_at" name="fake_comments[created_at][0]" value="{{ old("fake_comments.created_at.{$k}") }}">
								</td>
								<td><textarea class="comments-comment" name="fake_comments[comment][0]" rows="2">{{ old("fake_comments.comment.{$k}") }}</textarea></td>
								<td class="one column wide center aligned actions">
									<div class="mb-1">
										<button type="button" class="ui small button mr-0 circular icon fluid" data-action="reset">
											{{ __('Reset') }}
										</button>
									</div>
									<div>
										<i class="times grey circle big icon link" data-action="remove" title="{{ __('Remove') }}"></i>
										<i class="plus blue circle big icon link mx-0" data-action="add" title="{{ __('Add') }}"></i>
									</div>
								</td>
							</tr>
							@endforeach
							@else
							<tr>
								<td>
									<input type="text" class="comments-username" name="fake_comments[username][0]" >
								</td>
								<td class="one colum wide">
									<input type="datetime-local" class="comments-created_at" name="fake_comments[created_at][0]">
								</td>
								<td><textarea class="comments-comment" name="fake_comments[comment][0]" rows="2"></textarea></td>
								<td class="one column wide center aligned actions">
									<div class="mb-1">
										<button type="button" class="ui small button mr-0 circular icon fluid" data-action="reset">{{ __('Reset') }}</button>
									</div>
									<div>
										<i class="times grey circle big icon link" data-action="remove" title="{{ __('Remove') }}"></i>
										<i class="plus blue circle big icon link mx-0" data-action="add" title="{{ __('Add') }}"></i>
									</div>
								</td>
							</tr>
							@endif
						</tbody>
					</table>
				</div>
			</div>

			<div class="ui modal" id="files-list">
				<div class="content head p-1">
					<h3>@{{ drivesTitles[selectedDrive] }}</h3>
					
					<div class="ui icon input" v-if="!/yandex|amazon_s3|wasabi|gcs/.test(selectedDrive)">
					  <input type="text" placeholder="{{ __('Folder') }}..." v-model="parentFolder" spellcheck="false">
					  <i class="paper plane outline link icon" @click="setFolder"></i>
					</div>
				</div>

				<div class="content body" v-if="selectedDrive">
					<div class="items">
						<div class="item header">
							<span class="icon">-</span>
							<span class="name">{{ __('Filename') }}</span>
							<span class="type">{{ __('Type') }}</span>
							<span class="size">{{ __('Size') }}</span>
							<span class="modified">{{ __('Date') }}</span>
						</div>

						<div class="item" v-for="item in mainFilesList[selectedDrive]" :title="item.name" @click="setSelectedFile(item)">
							<span class="icon"><i class="circle outline inverted icon mx-0"></i></span>
							<span class="name">@{{ item.name }}</span>
							<span class="type">@{{ item.mime_type || item.mimeType || item.contentType || '-' }}</span>
							<span class="size">@{{ item.size }}</span>
							<span class="date">@{{ new Date(item.modifiedTime || item.client_modified || item.lastModified || item.modified || item.updated).toUTCString() }}</span>
						</div>
					</div>
				</div>

				<div class="actions">
					<div class="ui icon input large">
					  <input type="text" placeholder="{{ __('Search') }}..." v-model="searchFile" spellcheck="false">
					  <i class="search link icon" @click="searchFiles"></i>
					</div>

					<button v-if="googleDriveNextPageToken && selectedDrive === 'google'" 
									class="ui blue large circular button" 
									type="button"
									@click="googleDriveLoadMore($event)">
						{{ __('Load more files') }}
					</button>

					<button v-if="gcsNextResultToken && selectedDrive === 'gcs'" 
									class="ui blue large circular button" 
									type="button"
									@click="googleCloudStorageLoadMore($event)">
						{{ __('Load more files') }}
					</button>

					<button v-if="amazonS3Marker && selectedDrive === 'amazon_s3'" 
									class="ui blue large circular button" 
									type="button"
									@click="amazonS3LoadMore($event)">
						{{ __('Load more files') }}
					</button>

					<button v-if="wasabiMarker && selectedDrive === 'wasabi'" 
									class="ui blue large circular button" 
									type="button"
									@click="wasabiLoadMore($event)">
						{{ __('Load more files') }}
					</button>

					<button v-if="dropBoxCursor && selectedDrive === 'dropbox'" 
									class="ui blue large circular button"
									type="button"
									@click="dropBoxDriveLoadMore($event)">
						{{ __('Load more files') }}
					</button>

					<button v-if="yandexDiskOffset && selectedDrive === 'yandex'" 
									class="ui blue large circular button"
									type="button"
									@click="yandexDiskLoadMore($event)">
						{{ __('Load more files') }}
					</button>

					<button class="ui yellow large circular button"type="button" @click="closeDriveModal">{{ __('Close') }}</button>
				</div>
			</div>
		</div>
	</form>
	
	<form>
		<input type="file" name="download" data-destination="downloads" @change="uploadFileAsync" class="d-none">
	</form>

	<form>
		<input type="file" name="preview" data-destination="previews" @change="uploadFileAsync" class="d-none">
	</form>

	<form>
		<input type="file" name="cover" data-destination="covers" @change="uploadFileAsync" class="d-none" accept="image/*" >
	</form>

	<form>
		<input type="file" name="screenshots" data-destination="screenshots" @change="uploadFileAsync" class="d-none" accept=".zip">
	</form>

	<input type="file" class="d-none" id="image-editor-picker" accept="image/*,video/*">
</div>

<script type="application/javascript">
	'use strict';

	var app = new Vue({
  	el: '#product',
  	data: {
  		mainFilesList: {google: [], amazon_s3: [], dropbox: [], yandex: [], wasabi: [], gcs: []},
  		selectedDrive: '{{ old('file_host', 'local') }}',
  		googleDriveNextPageToken: null,
  		dropBoxCursor: null,
  		yandexDiskOffset: null,
  		amazonS3Marker: null,
  		gcsNextResultToken: null,
  		wasabiMarker: null,
  		drivePageSize: 20,
  		drivesTitles: {
  			google: '{{ __('Google Drive') }}', 
  			amazon_s3: '{{ __('Amazon S3') }}', 
  			dropbox: '{{ __('DropBox') }}', 
  			yandex: '{{ __('Yandex Disk') }}', 
  			wasabi: '{{ __('Wasabi') }}', 
  			gcs: '{{ __('Google Cloud Storage') }}'
  		},
  		searchFile: null,
  		parentFolder: null,
  		fileId: '{!! old('file_host') !== "local" ? old('file_name') : null !!}',
  		fileExtension: '{!! old('file_extension') !!}',
  		previewExtension: '{!! old('preview_extension') !!}',
  		localFileName: '',
  		freeForLimitedTime: true,
  		ajaxRequests: {
  			download: {}, 
  			cover: {}, 
  			screenshots: {}, 
  			preview: {}
  		},
  		oldUploads: {
  			download: "", 
  			cover: "", 
  			screenshots: "",
  			preview: ""
  		}
  	},
  	methods: {
  		browserMainFile: function(from)
  		{
  			if(from === 'local')
  			{  				
  				$('input[name="download"]').click();
  			}
  			else if(from === 'google')
  			{
  				this.googleDriveInit();
  			}
  			else if(from === 'amazon_s3')
  			{
  				this.amazonS3Init();
  			}
  			else if(from === 'gcs')
  			{
  				this.googleCloudStorageInit();
  			}
  			else if(from === 'wasabi')
  			{
  				this.wasabiInit();
  			}
  			else if(from === 'dropbox')
  			{
  				this.dropboxDriveInit();
  			}
  			else if(from === 'yandex')
  			{
  				this.yandexDiskInit();
  			}

  			if(!/^main_file_(upload|download)_link$/i.test(from))
  			{
  				this.selectedDrive = from;
  			}
  			else
  			{
  				$('input[name="'+from+'"]').show();
  			}

  			if(/^(google|amazon_s3|dropbox|yandex|wasabi|gcs)$/i.test(from))
  			{
  				$('#files-list').modal('show')
  			}
  		},
  		googleDriveLoadMore: function(e)
  		{
  			var e = e;

  			e.target.disabled = true;

  			if(this.googleDriveNextPageToken)
  			{
  				var payload = {
  					'files_host': 'GoogleDrive', 
						'page_size': this.drivePageSize, 
						'nextPageToken': this.googleDriveNextPageToken,
					};

  				$.post('{{ route('products.list_files') }}', payload, null, 'json')
  				.done(function(res)
  				{
  					if(!res.files_list)
  						return;

						app.googleDriveNextPageToken = res.files_list.nextPageToken || null;
  					
  					e.target.disabled = app.googleDriveNextPageToken ? false : true;

  					Vue.set(app.mainFilesList, 'google', 
  								  app.mainFilesList.google.concat(res.files_list.files || []));
  				})
  			}
  		},
  		amazonS3LoadMore: function(e)
  		{
  			var e = e;

  			e.target.disabled = true;

  			if(this.amazonS3Marker)
  			{
  				var payload = {
  					'files_host': 'AmazonS3', 
						'page_size': this.drivePageSize, 
						'marker': this.amazonS3Marker
					};

  				$.post('{{ route('products.list_files') }}', payload, null, 'json')
  				.done(function(res)
  				{
  					if(!res.files_list)
  						return;

						app.amazonS3Marker = res.files_list.marker || null;
  					e.target.disabled = res.files_list.has_more ? false : true;

  					Vue.set(app.mainFilesList, 'amazon_s3', 
  								  app.mainFilesList.amazon_s3.concat(res.files_list.files || []));
  				})
  			}
  		},
  		googleCloudStorageLoadMore: function(e)
  		{
  			var e = e;

  			e.target.disabled = true;

  			if(this.gcsNextResultToken)
  			{
  				var payload = {
  					'files_host': 'GoogleCloudStorage', 
						'maxResults': this.drivePageSize, 
						'pageToken': this.gcsNextResultToken
					};

  				$.post('{{ route('products.list_files') }}', payload, null, 'json')
  				.done(function(res)
  				{
  					if(!res.files_list)
  						return;

						app.gcsNextResultToken = res.files_list.nextResultToken || null;
  					e.target.disabled = res.files_list.has_more ? false : true;

  					Vue.set(app.mainFilesList, 'gcs', 
  								  app.mainFilesList.gcs.concat(res.files_list.files || []));
  				})
  			}
  		},
  		wasabiLoadMore: function(e)
  		{
  			var e = e;

  			e.target.disabled = true;

  			if(this.wasabiMarker)
  			{
  				var payload = {
  					'files_host': 'Wasabi', 
						'page_size': this.drivePageSize, 
						'marker': this.wasabiMarker
					};

  				$.post('{{ route('products.list_files') }}', payload, null, 'json')
  				.done(function(res)
  				{
  					if(!res.files_list)
  						return;

						app.wasabiMarker = res.files_list.marker || null;
  					e.target.disabled = res.files_list.has_more ? false : true;

  					Vue.set(app.mainFilesList, 'wasabi', 
  								  app.mainFilesList.wasabi.concat(res.files_list.files || []));
  				})
  			}
  		},
  		dropBoxDriveLoadMore: function(e)
  		{
  			var e = e;

  			e.target.disabled = true;

  			var payload = {
  				'files_host': 'DropBox', 
	  			'cursor': this.dropBoxCursor, 
	  			'limit': this.drivePageSize,
	  		};

				$.post('{{ route('products.list_files') }}', payload, null, 'json')
				.done(function(res)
				{
					if(!res.files_list)
						return;

					app.dropBoxCursor = res.files_list.has_more ? res.files_list.cursor : null;

					e.target.disabled = res.files_list.has_more ? false : true;

					Vue.set(app.mainFilesList, 'dropbox', 
  								app.mainFilesList.dropbox.concat(res.files_list.files || []));
				})
  		},
  		yandexDiskLoadMore: function(e)
  		{
  			var e = e;

  			e.target.disabled = true;

  			var payload = {
  				'files_host': 'YandexDisk', 
	  			'offset': this.yandexDiskOffset, 
	  			'limit': this.drivePageSize
	  		};

				$.post('{{ route('products.list_files') }}', payload, null, 'json')
				.done(function(res)
				{
					if(!res.files_list)
						return;

					app.yandexDiskOffset = res.files_list.offset;

					e.target.disabled = app.yandexDiskOffset === null;

					Vue.set(app.mainFilesList, 'yandex', 
  								app.mainFilesList.yandex.concat(res.files_list.items || []));
				})
  		},
  		setFolder: function()
  		{
  			if(this.selectedDrive === 'google')
  			{  				
  				this.googleDriveInit();
  			}
  			else if(this.selectedDrive === 'amazon_s3')
  			{
  				this.amazonS3Init();
  			}
  			else if(this.selectedDrive === 'wasabi')
  			{
  				this.wasabiInit();
  			}
  			else if(this.selectedDrive === 'dropbox')
  			{
  				this.dropboxDriveInit();
  			}
  		},
  		googleDriveInit: function()
  		{
				var payload = {
					'files_host': 'GoogleDrive', 
					'page_size': this.drivePageSize, 
					'parent': this.parentFolder,
					'keyword': this.searchFile,
				};

				$.post('{{ route('products.list_files') }}', payload, null, 'json')
				.done(function(res)
				{
					try
					{
						if(!res.files_list.files.length || null)
						{
							Vue.set(app.mainFilesList, 'google', []);
							return;
						}	
					}
					catch(error){}

					app.googleDriveNextPageToken = res.files_list.nextPageToken || null;
					
					Vue.set(app.mainFilesList, 'google', res.files_list.files);
				})
  		},
  		amazonS3Init: function()
  		{
				var payload = {
					'files_host': 'AmazonS3', 
					'page_size': this.drivePageSize, 
					'parent': this.parentFolder,
					'keyword': this.searchFile
				};

				$.post('{{ route('products.list_files') }}', payload, null, 'json')
				.done(function(res)
				{
					try
					{
						if(!res.files_list.files.length || null)
						{
							Vue.set(app.mainFilesList, 'amazon_s3', []);
							return;
						}	
					}
					catch(error){}

					app.amazonS3Marker = res.files_list.marker || null;
					
					Vue.set(app.mainFilesList, 'amazon_s3', res.files_list.files);
				})
  		},
  		googleCloudStorageInit: function()
  		{
  			var payload = {
					'files_host': 'GoogleCloudStorage', 
					'maxResults': this.drivePageSize, 
					'parent': this.parentFolder,
					'keyword': this.searchFile,
				};

				$.post('{{ route('products.list_files') }}', payload, null, 'json')
				.done(function(res)
				{
					console.log(res)

					try
					{
						if(!res.files_list.files.length || null)
						{
							Vue.set(app.mainFilesList, 'gcs', []);
						}	
					}
					catch(error){}

					app.gcsNextResultToken = res.files_list.nextResultToken || null;
					
					Vue.set(app.mainFilesList, 'gcs', res.files_list.files);
				})
  		},
  		wasabiInit: function()
  		{
				var payload = {
					'files_host': 'Wasabi', 
					'page_size': this.drivePageSize, 
					'parent': this.parentFolder,
					'keyword': this.searchFile
				};

				$.post('{{ route('products.list_files') }}', payload, null, 'json')
				.done(function(res)
				{
					try
					{
						if(!res.files_list.files.length || null)
						{
							Vue.set(app.mainFilesList, 'wasabi', []);
							return;
						}	
					}
					catch(error){}

					app.wasabiMarker = res.files_list.marker || null;
					
					Vue.set(app.mainFilesList, 'wasabi', res.files_list.files);
				})
  		},
  		dropboxDriveInit: function()
  		{
  			var payload = {
  				'files_host': 'DropBox', 
  				'limit': this.drivePageSize,
  				'path': this.parentFolder,
  				'keyword': this.searchFile,
  			};

				$.post('{{ route('products.list_files') }}', payload, null, 'json')
				.done(function(res)
				{
					try
					{
						if(!res.files_list.files.length || null)
						{
							Vue.set(app.mainFilesList, 'dropbox', []);
							return;
						}
					}
					catch(error){}

					app.dropBoxCursor = (res.files_list || {}).hasOwnProperty('has_more') ? res.files_list.cursor : null;
  				
  				Vue.set(app.mainFilesList, 'dropbox', res.files_list.files);
				})
  		},
  		yandexDiskInit: function()
  		{
  			var payload = {
  				'files_host': 'YandexDisk', 
  				'limit': this.drivePageSize,
  				'keyword': this.searchFile
  			};

				$.post('{{ route('products.list_files') }}', payload, null, 'json')
				.done(function(res)
				{
					try
					{
						if(!res.files_list.items.length || null)
						{
							Vue.set(app.mainFilesList, 'yandex', []);
							return;
						}
					}
					catch(error){}

					app.yandexDiskOffset = ((res.files_list.offset || null) > 0) ? res.files_list.offset : null;
  				
  				Vue.set(app.mainFilesList, 'yandex', res.files_list.items);
				})
  		},
  		searchFiles: function()
  		{
  			if(this.selectedDrive === 'google')
  			{  				
  				this.googleDriveInit();
  			}
  			else if(this.selectedDrive === 'amazon_s3')
  			{
  				this.amazonS3Init();
  			}
  			else if(this.selectedDrive === 'gcs') // Google cloud storage
  			{
  				this.googleCloudStorageInit();
  			}
  			else if(this.selectedDrive === 'wasabi')
  			{
  				this.wasabiInit();
  			}
  			else if(this.selectedDrive === 'dropbox')
  			{
  				this.dropboxDriveInit();
  			}
  			else if(this.selectedDrive === 'yandex')
  			{
  				this.yandexDiskInit();
  			}
  		},
  		setSelectedFile: function(file)
  		{	
  			let ext = file.mime_type || file.mimeType || file.name.split('.').pop() || '';

  			if(ext.split('/').length === 2)
    		{
    			ext = ext.split('/')[1];
    		}

  			this.fileExtension = ext;
  			this.fileId = file.id;

  			$('#files-list').modal('hide');
  		},
  		removeSelectedFile: function()
  		{
  			this.selectedDrive = '';
  			this.fileId 			 = null;
  			this.fileExtension = null;

  			Vue.nextTick(function()
  			{
  				$('.ui.dropdown.files').dropdown();
  			})
  		},
  		getFileExtension(file)
  		{
  			let fileName = file.name;
	  		
	  		let ext = fileName.split('.').pop() || '';
	  				ext = (ext !== fileName) ? ext : '';
	  		
	  		ext = ext.length ? ext : (mimetypes[file.type] || '');

	  		if(ext.split('/').length === 2)
    		{
    			ext = ext.split('/')[1];
    		}

	  		return ext;
  		},
  		closeDriveModal: function()
  		{
  			$('#files-list').modal('hide')
  		},
  		setDefaultDrive: function()
  		{
  			this.selectedDrive = 'local';
  		},
  		selectFile: function(name)
  		{
  			$('input[name="'+name+'"]').click()
  		},
  		abortUpload: function(name)
  		{
  			this.ajaxRequests[name].abort();

  			Vue.set(app.ajaxRequests, name, {});

  			$('input[name="'+name+'"]').closest('form')[0].reset()
  		},
  		removeUploadedFile: function(name)
  		{
  			$.post('{{ route('products.delete_file_async') }}', {path: this.ajaxRequests[name].file_path})
  			.done(function()
  			{
  				$('input[name="'+name+'"]').closest('form')[0].reset();

  				Vue.set(app.ajaxRequests, name, {});
  			})
  			.always(function()
  			{
  				Vue.nextTick(function()
  				{
  					$('.ui.dropdown').dropdown();
  				})
  			})
  		},
  		deleteExistingFile: function(path, name)
  		{
  			$.post('{{ route('products.delete_file_async') }}', {path: path})
  			.done(function()
  			{
  				Vue.set(app.oldUploads, name, '');
  			})
  			.always(function()
  			{
  				Vue.nextTick(function()
  				{
  					$('.ui.dropdown').dropdown();
  				})
  			})
  		},
  		deleteScreenshot: function(e, path)
  		{
  			var _this = $(e.target);

  			$.post('{{ route('products.delete_file_async') }}', {path: path})
  			.done(function()
  			{
  				_this.closest('.image').remove();

  				if($('.screenshots .image').length === 0)
  				{
  					Vue.set(app.oldUploads, 'screenshots', '');
  				}
  			})
  		},
  		uploadFileAsync: function(e)
  		{
  			var file = e.target;
  			var name = file.name;

  			Vue.set(app.ajaxRequests, name, {});

  			var destination = file.getAttribute('data-destination');

  			var formData = new FormData();

				formData.append('file', file.files[0]);
				formData.append('destination', destination);
				formData.append('id', {{ $product_id }})
				
				var ajaxRequests = this.ajaxRequests;
  				
	  		ajaxRequests[name] = $.ajax({
            url: '{{ route('products.upload_file_async') }}',
            xhr: function()
            {
            	var xhr = new window.XMLHttpRequest();

            	Vue.set(app.ajaxRequests[name], 'progress', 0);

            	xhr.upload.addEventListener('progress', function(event)
            	{
            		if(event.lengthComputable)
            		{
            			var complete = Number((event.loaded / event.total) * 100).toFixed();

            			Vue.set(app.ajaxRequests[name], 'progress', complete);
            		}
            	}, false);

            	return xhr;
            },
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            cache: false,
            beforeSend: function()
            {

            },
            success: function(response)
            {
              if(response.status === 'success')
              { 
              	Vue.set(app.ajaxRequests[name], 'file_name', response.file_name);
              	Vue.set(app.ajaxRequests[name], 'file_path', response.file_path);

              	if(/^(previews|downloads)$/i.test(destination))
              	{
              		let ext = app.getFileExtension(file.files[0]);

              		if(ext.split('/').length === 2)
              		{
              			ext = ext.split('/')[1];
              		}

              		if(destination === "previews")
              		{
              			app.previewExtension = ext.length ? ext : response.extension;	
              		}
              		else
              		{
              			app.fileExtension = ext.length ? ext : response.extension;	
              		}
              	}
              }
            },
            error: function()
            {

            }
        });

        this.ajaxRequests = ajaxRequests;
  		},
  		deleteFileAsync: function(path)
  		{
  			$.post('{{ route('products.delete_file_async') }}', {path: path})
  			.done(function()
  			{
  				
  			})
  		},
  		hasProgress: function(name)
  		{
  			if(this.ajaxRequests.hasOwnProperty(name))
  			{
  				return this.ajaxRequests[name].hasOwnProperty('progress');
  			}
  			
  			return false;
  		},
  		uploadInProgress: function(name)
  		{
  			if(this.hasProgress(name))
  			{
  				var progress = this.ajaxRequests[name].progress;

  				return (progress == 0 || progress <= 100) && !this.ajaxRequests[name].hasOwnProperty('file_name');
  			}

  			return false;
  		},
  		finishedUploading: function(name)
  		{
  			if(this.hasProgress(name))
  			{
  				return this.ajaxRequests[name].progress == 100;
  			}

  			return false;
  		},
  		inputIsOff: function(name)
  		{
  			return this.uploadInProgress(name) || this.finishedUploading(name);
  		},
  		anyInputOff: function()
  		{
  			var inputs = ['download', 'cover', 'screenshots', 'preview'];
  			var app 	 = this;

  			for(var k = 0; k < inputs.length; k++)
  			{
  				if(this.uploadInProgress(inputs[k]))
  					return true;
  			}

  			return false;
  		},
  	},
  	watch: {

  	},
  	mounted: function()
  	{

  	}
  })

	$(function()
  {
  	$(document).on('change', 'input[name="preview"]', function()
  	{
  		let fileName = $(this)[0].files[0].name;
  		let fileType = $(this)[0].files[0].type;
  		
  		let ext = fileName.split('.').pop() || '';
  				ext = (ext !== fileName) ? ext : '';
  		
  		app.previewExtension = ext.length ? ext : (mimetypes[fileType] || '');
  	})

  	@if(config('app.products_by_country_city'))
	  	var countriesCities = @json(config('app.countries_cities'));

	  	$('.ui.dropdown.countries').dropdown({
	  		onChange: function(value, text, $choice)
	  		{
	  			if(countriesCities.hasOwnProperty(value))
	  			{
		  			$('.ui.dropdown.cities').dropdown({
		  				values: countriesCities[value].sort().map(function(city)
		  				{
		  					return {value: city, name: city};
		  				}).concat({value: '', name: '&nbsp;'})
		  			})
	  			}
	  		}
	  	})

	  	@if($country = old('country_city.country'))
			$('.ui.dropdown.cities').dropdown({
				values: countriesCities['{{ $country }}'].sort().map(function(city)
				{
					return {value: city, name: city};
				}).concat({value: '', name: '&nbsp;'})
			})

			@if($city = old('country_city.city'))
			$('.ui.dropdown.cities').dropdown('set selected', '{{ $city }}');
			@endif
	  	@endif
  	@endif

  	@if(config('app.html_editor') == 'summernote')
  	$('.html-editor').summernote({
	    placeholder: '...',
	    tabsize: 2,
	    height: 350,
	    tooltip: false
	  });
	  @else
		window.tinyMceOpts = {
			encoding: "html",
		  plugins: 'print preview paste importcss searchreplace autolink autosave save directionality code visualblocks visualchars fullscreen image link media template codesample table charmap hr pagebreak nonbreaking anchor toc insertdatetime advlist lists wordcount imagetools textpattern noneditable help charmap quickbars emoticons' /* bbcode */,
		  menubar: 'file edit view insert format tools table help',
		  toolbar: 'undo redo | bold italic underline strikethrough | fontselect fontsizeselect formatselect | alignleft aligncenter alignright alignjustify | outdent indent |  numlist bullist | forecolor backcolor removeformat | pagebreak | charmap emoticons | fullscreen  preview save print | insertfile image media template link anchor codesample | ltr rtl',
		  toolbar_sticky: true,
		  autosave_ask_before_unload: false,
		  autosave_interval: '0',
		  autosave_prefix: '{path}{query}-{id}-',
		  autosave_restore_when_empty: false,
		  autosave_retention: '2m',
		  image_advtab: true,
		  file_picker_types: 'image media',
		  importcss_append: true,
		  media_live_embeds: true,
		 	media_filter_html: false,
		  images_dataimg_filter: img => 
		  {
      	return img.hasAttribute('internal-blob');
  		},
		  file_picker_callback: function (callback, value, meta) 
		  {
	    	$('#image-editor-picker').on('change', function()
	    	{
	    		let file = $(this)[0].files[0];

	    		let reader = new FileReader();

		      reader.onload = () =>
		      {
		        callback(reader.result, { title: file.name });
		      };
		      
		      reader.readAsDataURL(file);
		    }).click();
		  },
		  video_template_callback: data => 
		  {
		  	return `<video width="${data.width}" height="${data.height}" controls="controls">
		  	  <source src="${data.source}" type="${data.sourcemime}"/>
		  	</video>`;
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


	  $('#product .tabs .menu .item')
	  .tab({
	    context: 'parent'
	  })


		$('input[name="category"]').on('change', function()
		{
			setSubcategories($(this).val());
		})


		function setSubcategories(parentId = null, selectedValues = '')
		{
			var subcategories = @json($category_children ?? (object)[]);

			if(!isNaN(parentId))
			{
				var values = [];

				if(Object.keys(subcategories).length)
				{
					if(!subcategories.hasOwnProperty(parentId))
						return;

					for(var k in (subcategories[parentId] || []))
					{
						var subcategory = subcategories[parentId][k];

						values.push({name: subcategory.name, value: subcategory.id});
					}	
				}

				$('#subcategories').dropdown('clear').dropdown({values: values});

				if(selectedValues.length)
				{
					$('input[name="subcategories"]').val(selectedValues);
					
					$('#subcategories').dropdown();
				}
			}
		}

		@if(old('category'))
		{
			setSubcategories({{ old('category') }}, '{{ old('subcategories') }}');
		}
		@endif

		$('#files-list').modal({
			closable: false
		})

		$('input[name="download"]').on('change', function()
		{
			app.fileId = null;
			app.localFileName = $(this)[0].files[0].name;
		})


		$('.ui.review.rating').rating({
			onRate: function(rating)
			{
				$(this).closest('td').find('input[type="hidden"]').val(rating)
			}
		})
  })
</script>

@endsection