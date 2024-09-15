@extends('back.master')

@section('title', __('File storage services'))



@section('additional_head_tags')

<script type="application/javascript">
	'use strict';
  var dropboxCurrentAccount 					= '{{ route('dropbox_get_current_user') }}';
  var dropBoxRedirectUri 							= '{{ url()->current() }}';
  var yandexDiskCode2AccessTokenRoute = '{{ route('yandex_disk_get_refresh_token') }}';
</script>

@endsection



@section('content')

<form class="ui large main form" method="post" spellcheck="false" action="{{ route('settings.update', 'files_host') }}">

	<div class="field">
		<button type="submit" class="ui large circular labeled icon button mx-0">
		  <i class="save outline icon mx-0"></i>
		  {{ __('Update') }}
		</button>
	</div>

	@if($errors->any())
    @foreach ($errors->all() as $error)
   <div class="ui negative fluid small message">
   	<i class="times icon close"></i>
   	{{ $error }}
   </div>
    @endforeach
	@endif


	@if(session('settings_message'))
	<div class="ui positive fluid message">
		<i class="times icon close"></i>
		{{ session('settings_message') }}
	</div>
	@endif
	
	<div class="ui fluid divider mb-0"></div>
	
	<div class="one column grid" id="settings">
		<div class="ui three doubling stackable cards mt-2">
			<!-- Google Cloud Storage -->
			<div class="ui fluid card">

				<div class="content googlecloudstorage" title="{{ __("Google cloud storage") }}">
					<h3 class="header">
						<img loading="lazy" src="/assets/images/google_cloud_storage.webp" alt="Google cloud storage" class="ui avatar image mr-1">{{ __("G. Cloud Storage") }}

						<div class="checkbox-wrapper">
							<div class="ui fitted toggle checkbox">
						    <input 
						    	type="checkbox" 
						    	name="google_cloud_storage[enabled]"
						    	@if(!empty(old('google_cloud_storage.enabled')))
									{{ old('google_cloud_storage.enabled') ? 'checked' : '' }}
									@else
									{{ ($settings->google_cloud_storage->enabled ?? null) ? 'checked' : '' }}
						    	@endif
						    >
						    <label></label>
						  </div>
						</div>
					</h3>
				</div>


				<div class="content">
					<div class="field">
						<label>{{ __('Project ID') }}</label>
						<input type="text" name="google_cloud_storage[project_id]" value="{{ old('google_cloud_storage.project_id', $settings->google_cloud_storage->project_id ?? null) }}" placeholder="...">
					</div>

					<div class="field">
						<label>{{ __('Private key id') }}</label>
						<textarea name="google_cloud_storage[private_key_id]" rows="1" placeholder="...">{{ old('google_cloud_storage.private_key_id', $settings->google_cloud_storage->private_key_id ?? null) }}</textarea>
					</div>

					<div class="field">
						<label>{{ __('Private key') }}</label>
						<textarea name="google_cloud_storage[private_key]" rows="1" placeholder="...">{{ old('google_cloud_storage.private_key', $settings->google_cloud_storage->private_key ?? null) }}</textarea>
					</div>

					<div class="two stackable fields">
						<div class="field">
							<label>{{ __('Client email') }}</label>
							<input type="text" name="google_cloud_storage[client_email]" placeholder="..." value="{{ old('google_cloud_storage.client_email', $settings->google_cloud_storage->client_email ?? null) }}">
						</div>

						<div class="field">
							<label>{{ __('Client id') }}</label>
							<input type="text" name="google_cloud_storage[client_id]" placeholder="..." value="{{ old('google_cloud_storage.client_id', $settings->google_cloud_storage->client_id ?? null) }}">
						</div>
					</div>

					<div class="field">
						<label>{{ __('Auth provider x509 cert url') }}</label>
						<input type="text" name="google_cloud_storage[auth_provider_x509_cert_url]" placeholder="..." value="{{ old('google_cloud_storage.auth_provider_x509_cert_url', $settings->google_cloud_storage->auth_provider_x509_cert_url ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Client x509 cert url') }}</label>
						<input type="text" name="google_cloud_storage[client_x509_cert_url]" placeholder="..." value="{{ old('google_cloud_storage.client_x509_cert_url', $settings->google_cloud_storage->client_x509_cert_url ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Bucket') }}</label>
						<input type="text" name="google_cloud_storage[bucket]" placeholder="..." value="{{ old('google_cloud_storage.bucket', $settings->google_cloud_storage->bucket ?? null) }}">
					</div>

					<div class="field inline mb-0">
						<button class="ui circular basic large button" type="button" onclick="this.nextElementSibling.click()">{{ __('Config file') }}</button>
						<input type="file" accept=".json" class="d-none" id="gcs-config-file">
						<button class="ui circular large blue button mr-0" type="button" onclick="testGoogleCloudStorageConnection(event)">{{ __('Test connection') }}</button>
					</div>
				</div>
			</div>

			<!-- Amazon S3 -->
			<div class="ui fluid card">
				<div class="content dropbox">
					<h3 class="header">
						<img loading="lazy" src="{{ asset_('assets/images/amazon-s3.png') }}" alt="Amazon S3" class="ui avatar image mr-1">Amazon S3

						<div class="checkbox-wrapper">
							<div class="ui fitted toggle checkbox">
						    <input 
						    	type="checkbox" 
						    	name="amazon_s3[enabled]"
						    	@if(!empty(old('amazon_s3.enabled')))
									{{ old('amazon_s3.enabled') ? 'checked' : '' }}
									@else
									{{ ($settings->amazon_s3->enabled ?? null) ? 'checked' : '' }}
						    	@endif
						    >
						    <label></label>
						  </div>
						</div>
					</h3>
				</div>

				<div class="content">
					<div class="field">
						<label>{{ __('Access key ID') }}</label>
						<input type="text" name="amazon_s3[access_key_id]" placeholder="..." value="{{ old('amazon_s3.access_key_id', $settings->amazon_s3->access_key_id ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Secret key') }}</label>
						<input type="text" name="amazon_s3[secret_key]" placeholder="..." value="{{ old('amazon_s3.secret_key', $settings->amazon_s3->secret_key ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Bucket') }}</label>
						<input type="text" name="amazon_s3[bucket]" value="{{ old('amazon_s3.bucket', $settings->amazon_s3->bucket ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Region') }}</label>
						<input type="text" name="amazon_s3[region]" value="{{ old('amazon_s3.region', $settings->amazon_s3->region ?? 'us-west-2') }}">
					</div>

					<div class="field">
						<label>{{ __('Version') }}</label>
						<input type="text" name="amazon_s3[version]" readonly value="{{ old('amazon_s3.version', $settings->amazon_s3->version ?? 'latest') }}">
					</div>

					<div class="field mb-0">
						<button class="ui circular large blue button" type="button" onclick="testAmazonS3Connection(this)">{{ __('Test connection') }}</button>
					</div>
				</div>
			</div>

			<!-- Wasabi -->
			<div class="ui fluid card">
				<div class="content dropbox">
					<h3 class="header">
						<img loading="lazy" src="{{ asset_('assets/images/wasabi.png') }}" alt="Wasabi" class="ui avatar image mr-1">{{ __("Wasabi") }}

						<div class="checkbox-wrapper">
							<div class="ui fitted toggle checkbox">
						    <input 
						    	type="checkbox" 
						    	name="wasabi[enabled]"
						    	@if(!empty(old('wasabi.enabled')))
									{{ old('wasabi.enabled') ? 'checked' : '' }}
									@else
									{{ ($settings->wasabi->enabled ?? null) ? 'checked' : '' }}
						    	@endif
						    >
						    <label></label>
						  </div>
						</div>
					</h3>
				</div>

				<div class="content">
					<div class="field">
						<label>{{ __('Access key') }}</label>
						<input type="text" name="wasabi[access_key]" placeholder="..." value="{{ old('wasabi.access_key', $settings->wasabi->access_key ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Secret key') }}</label>
						<input type="text" name="wasabi[secret_key]" placeholder="..." value="{{ old('wasabi.secret_key', $settings->wasabi->secret_key ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Bucket') }}</label>
						<input type="text" name="wasabi[bucket]" value="{{ old('wasabi.bucket', $settings->wasabi->bucket ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Region') }}</label>
						<input type="text" name="wasabi[region]" value="{{ old('wasabi.region', $settings->wasabi->region ?? 'us-west-1') }}">
					</div>

					<div class="field">
						<label>{{ __('Version') }}</label>
						<input type="text" name="wasabi[version]" readonly value="{{ old('wasabi.version', $settings->wasabi->version ?? 'latest') }}">
					</div>

					<div class="field mb-0">
						<button class="ui circular large blue button" type="button" onclick="testWasabiConnection(this)">{{ __('Test connection') }}</button>
					</div>
				</div>
			</div>
		</div>

		<div class="ui three doubling stackable cards mt-1">
			<!-- GOOGLE DRIVE -->
			<div class="ui fluid card">
				<div class="content googledrive">
					<h3 class="header">
						<img loading="lazy" src="{{ asset_('assets/images/google_drive.webp') }}" alt="Wasabi" class="ui avatar image mr-1">{{ __("Google Drive") }}

						<div class="checkbox-wrapper">
							<div class="ui fitted toggle checkbox">
						    <input 
						    	type="checkbox" 
						    	name="google_drive[enabled]"
						    	@if(!empty(old('google_drive.enabled')))
									{{ old('google_drive.enabled') ? 'checked' : '' }}
									@else
									{{ ($settings->google_drive->enabled ?? null) ? 'checked' : '' }}
						    	@endif
						    >
						    <label></label>
						  </div>
						</div>
					</h3>
				</div>
				
				<div class="content">
					@if($settings->google_drive->connected_email ?? null)
					<div class="ui basic green message p-1-hf d-flex">
						<span class="ui basic green label circular-corner large">{{ ucfirst($settings->google_drive->connected_email ?? 'Null') }}</span>	
					</div>
					@endif
					
					<div class="field">
						<label>{{ __('App Default Folder ID') }} ({{ __('Optional') }})</label>
						<input type="text" name="google_drive[folder_id]" value="{{ old('google_drive.folder_id', $settings->google_drive->folder_id ?? null) }}" placeholder="E.g. 1FREVJPb_R_cdNbpsfo2plpYlzerfEGV9">
					</div>

					<div class="field">
						<label>{{ __('API key') }}</label>
						<input type="text" name="google_drive[api_key]" placeholder="..." value="{{ old('google_drive.api_key', $settings->google_drive->api_key ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Client ID') }}</label>
						<input type="text" name="google_drive[client_id]" placeholder="..." value="{{ old('google_drive.client_id', $settings->google_drive->client_id ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Secret ID') }}</label>
						<input type="text" name="google_drive[secret_id]" placeholder="..." value="{{ old('google_drive.secret_id', $settings->google_drive->secret_id ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Chunk size') }} <sup>{{ __('MB') }}</sup></label>
						<input type="number" name="google_drive[chunk_size]" placeholder="..." value="{{ old('google_drive.chunk_size', $settings->google_drive->chunk_size ?? '1') }}">
					</div>

					<div class="field">
						<button class="ui circular large yellow button" type="button" onclick="authorizeGoogleDriveApp()">{{ __('Connect account') }}</button>
					</div>

					<div class="field">
						<label>{{ __('Refresh token') }}</label>
						<input type="text" name="google_drive[refresh_token]" readonly value="{{ old('google_drive.refresh_token', $settings->google_drive->refresh_token ?? null) }}">

						<input type="hidden" name="google_drive[connected_email]" readonly value="{{ old('google_drive.connected_email', $settings->google_drive->connected_email ?? null) }}">
						<input type="hidden" name="google_drive[id_token]" readonly value="{{ old('google_drive.id_token', $settings->google_drive->id_token ?? null) }}">
					</div>
				</div>
			</div>
			

			<!-- DROPBOX -->
			<div class="ui fluid card">
				<div class="content dropbox">
					<h3 class="header">
						<img loading="lazy" src="{{ asset_('assets/images/dropbox.webp') }}" alt="Wasabi" class="ui avatar image mr-1">{{ __("DropBox") }}

						<div class="checkbox-wrapper">
							<div class="ui fitted toggle checkbox">
						    <input 
						    	type="checkbox" 
						    	name="dropbox[enabled]"
						    	@if(!empty(old('dropbox.enabled')))
									{{ old('dropbox.enabled') ? 'checked' : '' }}
									@else
									{{ ($settings->dropbox->enabled ?? null) ? 'checked' : '' }}
						    	@endif
						    >
						    <label></label>
						  </div>
						</div>

					</h3>
				</div>

				<div class="content">
					@if($settings->dropbox->current_account ?? null)
					<div class="ui basic green message p-1-hf d-flex">
						<span class="ui basic green label circular-corner large">{{ ucfirst($settings->dropbox->current_account ?? 'Null') }}</span>	
					</div>
					@endif


					<div class="field">
						<label>{{ __('App Default Folder Path') }} ({{ __('Optional') }})</label>
						<input type="text" name="dropbox[folder_path]" value="{{ old('dropbox.folder_path', $settings->dropbox->folder_path ?? null) }}" placeholder="/PATH">
					</div>

					<div class="field">
						<label>{{ __('App key') }}</label>
						<input type="text" name="dropbox[app_key]" placeholder="..." value="{{ old('dropbox.app_key', $settings->dropbox->app_key ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('App secret') }}</label>
						<input type="text" name="dropbox[app_secret]" placeholder="..." value="{{ old('dropbox.app_secret', $settings->dropbox->app_secret ?? null) }}">
					</div>

					<div class="field">
						<button class="ui circular large yellow button" type="button" onclick="authorizeDropBoxApp()">{{ __('Connect account') }}</button>
					</div>

					<div class="field">
						<label>{{ __('Access token') }}</label>
						<input type="text" name="dropbox[access_token]" value="{{ old('dropbox.access_token', $settings->dropbox->access_token ?? null) }}">

						<input type="hidden" name="dropbox[current_account]" value="{{ old('dropbox.current_account', $settings->dropbox->current_account ?? null) }}">
					</div>
				</div>
			</div>


			<!-- YANDEX DISK -->
			<div class="ui fluid card">
				<div class="content dropbox">
					<h3 class="header">
						<img loading="lazy" src="{{ asset_('assets/images/yandex_disk.webp') }}" alt="Wasabi" class="ui avatar image mr-1">{{ __("Yandex Disk") }}

						<div class="checkbox-wrapper">
							<div class="ui fitted toggle checkbox">
						    <input 
						    	type="checkbox" 
						    	name="yandex[enabled]"
						    	@if(!empty(old('yandex.enabled')))
									{{ old('yandex.enabled') ? 'checked' : '' }}
									@else
									{{ ($settings->yandex->enabled ?? null) ? 'checked' : '' }}
						    	@endif
						    >
						    <label></label>
						  </div>
						</div>

					</h3>
				</div>

				<div class="content">
					<div class="field">
						<label>{{ __('Default folder path') }}</label>
						<input type="text" name="yandex[folder_path]" placeholder="..." value="{{ old('yandex.folder_path', $settings->yandex->folder_path ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('App ID') }}</label>
						<input type="text" name="yandex[client_id]" placeholder="..." value="{{ old('yandex.client_id', $settings->yandex->client_id ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('App password') }}</label>
						<input type="text" name="yandex[secret_id]" placeholder="..." value="{{ old('yandex.secret_id', $settings->yandex->secret_id ?? null) }}">
					</div>

					<div class="field">
						<button class="ui circular large yellow button" type="button" onclick="authorizeYandexApp()">{{ __('Connect account') }}</button>
					</div>

					<div class="field">
						<label>{{ __('Refresh token') }}</label>
						<input type="text" name="yandex[refresh_token]" readonly value="{{ old('yandex.refresh_token', $settings->yandex->refresh_token ?? null) }}">
					</div>
				</div>
			</div>
		</div>

		<div class="ui fluid card mt-2">
			<div class="content">
				<div class="header">{{ __('Remote files configuration') }}</div>
			</div>
			<div class="content">
				<div class="field">
					<label>{{ __('Headers') }} <sup>({{ __('One per line') }})</sup></label>
					<div class="headers">
						<textarea rows="2" name="remote_files[headers]" placeholder="{{ __('Name: Value') }}">@foreach($settings->remote_files->headers ?? [] as $name => $value){!! $name.': '.$value.PHP_EOL !!}@endforeach</textarea>
					</div>
		    </div>
			</div>
			<div class="content">
				<div class="field">
		      <label>{{ __('Body') }} <sup>({{ __('One per line') }})</sup></label>
		      <div class="body">
		      	<textarea rows="2" name="remote_files[body]" placeholder="{{ __('Name: Value') }}">@foreach($settings->remote_files->body ?? [] as $name => $value){!! $name.': '.$value.PHP_EOL !!}@endforeach</textarea>
		      </div>
		    </div>
			</div>
		</div>		
	</div>
</form>

<script>
	'use strict';

	$(function()
	{
		$('#settings .ui.checkbox').checkbox();
		
		$('#gcs-config-file').on('change', function(e)
		{
			let file = $(this)[0].files[0];
			let reader = new FileReader();

			reader.addEventListener('load', (event) => 
			{
				try
				{
					let config = JSON.parse(event.target.result);

					if(config.hasOwnProperty('type'))
					{
						for(let i in config)
						{
							$(`[name="google_cloud_storage[${i}]"]`).val(config[i])
						}
					}
				}
				catch(error)
				{

				}
			});

			reader.readAsText(file);
		})

		$('#settings input, #settings textarea').on('keydown', function(e) 
		{
		    if((e.which == '115' || e.which == '83' ) && (e.ctrlKey || e.metaKey))
		    {		        
		        $('form.main').submit();

		  			e.preventDefault();

		        return false;
		    }
		    else
		    {
		        return true;
		    }
		})
	})
</script>

@endsection