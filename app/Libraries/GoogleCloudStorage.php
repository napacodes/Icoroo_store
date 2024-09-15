<?php

	namespace App\Libraries;

	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\{ DB, Cache, Session };
	use GuzzleHttp\Client;
	use GuzzleHttp\Exception\BadResponseException;
	use GuzzleHttp\Psr7;
	use Google\Cloud\Storage\{ StorageClient, StorageObject };
	use App\Models\{ Temp_Direct_Url };

	class GoogleCloudStorage 
	{
		public static function storage_client()
		{
			$config_user = collect(config("filehosts.google_cloud_storage", []))->except(['bucket', 'enabled', 'api_key', 'connected_email', 'id_token'])->toArray();

			$config_base = [
			  "type" => "service_account", // "authorized_user"
			  "project_id" => "",
			  "private_key_id" => "",
			  "private_key" => "",
			  "client_email" => "",
			  "client_id" => "",
			  "auth_uri" => "https://accounts.google.com/o/oauth2/auth",
			  "token_uri" => "https://oauth2.googleapis.com/token",
			  "auth_provider_x509_cert_url" => "https://www.googleapis.com/oauth2/v1/certs",
			  "client_x509_cert_url" => ""
			];

			$config = array_merge($config_base, $config_user);

			return (new StorageClient(['keyFile' => $config]));
		}


		public static function get_bucket($bucketName = null)
		{
			return Self::storage_client()->bucket($bucketName ?? config("filehosts.google_cloud_storage.bucket"));
		}


		public static function test_connexion(Request $request)
		{
			try
			{
				$config = [
				  "type" => "service_account", // "authorized_user"
				  "project_id" => $request->input('google_cloud_storage.project_id'),
				  "private_key_id" => $request->input('google_cloud_storage.private_key_id'),
				  "private_key" => $request->input('google_cloud_storage.private_key'),
				  "client_email" => $request->input('google_cloud_storage.client_email'),
				  "client_id" => $request->input('google_cloud_storage.client_id'),
				  "auth_uri" => "https://accounts.google.com/o/oauth2/auth",
				  "token_uri" => "https://oauth2.googleapis.com/token",
				  "auth_provider_x509_cert_url" => "https://www.googleapis.com/oauth2/v1/certs",
				  "client_x509_cert_url" => $request->input('google_cloud_storage.client_x509_cert_url'),
				];

				$response = (new StorageClient(['keyFile' => $config]))->getServiceAccount();

				if(filter_var($response, FILTER_VALIDATE_EMAIL))
				{
					return __("Success");
				}
			}
			catch(\Exception $e)
			{
				return $e->getMessage();
			}
		}



		public static function list_files(Request $request)
		{
	    $bucket = Self::get_bucket($request->bucket);
	    	
	    $options = [
	    	"maxResults" => $request->maxResults ?? 20,
	    	"pageToken" => $request->pageToken,
	    	"fields" => "items/kind,items/id,items/name,items/updated,items/contentType,items/size,nextPageToken",
	    	"prefix" => $request->keyword,
	    	"delimiter" => $request->parent,
	    	"versions" => 0,
	    ];

	    $response = $bucket->objects($options);

	    $response = [
				"iterateByPage" => $response->iterateByPage(),
				"valid" => $response->valid(),
	    ];

	    $nextResultToken = $response["iterateByPage"]->nextResultToken();
	    
	    $items = array_reduce($response["iterateByPage"]->current() ?? [], function($carry, $item)
	    {
	    	$item = $item->info();
	    	$item['id'] = $item['name'];
	    	$carry[] = $item;
	    	return $carry;
	    }, []);

	    return json([
	    	"files_list" => [
	    		"files" => $items,
	    		"has_more" => $nextResultToken ? 1 : 0,
	    		"nextResultToken" => $nextResultToken
	    	]
	    ]);
		}



		public static function object_exists(string $objectName, string $bucketName = null): bool
		{
			return Self::get_bucket($bucketName)->object($objectName)->exists();
		}



		public static function download(array $config)
		{
			$objectName = $config['item_id'];
			$cache_id 	= $config['cache_id'];
			$expiry 		= $config['expiry'] ?? 86400;
			$bucketName = $config['bucketName'] ?? null;

			$temp_url = Temp_Direct_Url::where(['product_id' => $cache_id, "host" => "gcs"])->where('expiry', '>=', time())->first();

			if(!$temp_url)
			{
				if(!Self::object_exists($objectName, $bucketName))
				{
					exists_or_abort(null, __("File doesn't exist."));
				}

				$object = Self::get_bucket($bucketName)->object($objectName);

				$signed_url = $object->signedUrl(
		        new \DateTime("{$expiry} second"),
		        [
		            'version' => 'v4',
		        ]
		    );

				DB::delete("DELETE FROM temp_direct_urls WHERE product_id = ?", [$cache_id]);

				$temp_url = Temp_Direct_Url::create([
					"product_id" => $cache_id,
					"host" => "gcs",
					"url" => $signed_url,
					"expiry" => time()+$expiry,
				]);
			}

			return redirect()->away($temp_url->url);
		}



		public static function getAccessToken()
		{
			if(Cache::has('google_cloud_storage_access_token'))
			{
				return ['status' => 1, 'access_token' => Cache::get('google_cloud_storage_access_token')];
			}

			$headers = [
				'Host: www.googleapis.com', 
				'Content-Type: application/x-www-form-urlencoded'
			];

			$client = new GuzzleCLient(['verify' => false, 'headers' => $headers, 'http_errors' => true]);

			try 
			{
				$payload = [
					'client_id' 		=> config("filehosts.google_cloud_storage.client_id"),
					'client_secret'	=> config("filehosts.google_cloud_storage.secret_id"),
					'refresh_token' => config("filehosts.google_cloud_storage.refresh_token"),
					'grant_type' 		=> 'refresh_token'
				];

				$response = $client->request('POST', 'https://www.googleapis.com/oauth2/v4/token', ['form_params' => $payload]);

				if($response->getStatusCode() === 200)
				{
					$data = json_decode((string)$response->getBody());

					if($data->access_token ?? null)
					{
						Cache::put('google_cloud_storage_access_token', $data->access_token, now()->addMinutes(55));

						return ['status' => 1, 'access_token' => $data->access_token];
					}
				}
			}
			catch(BadResponseException $e)
			{
				return ['status' => 0, 'error' => Psr7\Message::toString($e->getResponse())];
			}
		}



		private static function client()
		{
			$response = Self::getAccessToken();

			if(!$response['status'])
			{
				exists_or_abort(null, $response['error']);
			}

			return new Client(['verify' => false, 'headers' => ["Authorization" => "Bearer {$response['access_token']}"], 'http_errors' => true]);
		}
	}