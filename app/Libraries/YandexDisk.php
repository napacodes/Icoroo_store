<?php

	namespace App\Libraries;

	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\{ Cache, DB };
	use App\Models\{ Temp_Direct_Url };
	use GuzzleHttp\Client;


	class YandexDisk 
	{

		/** 
		* Get Refresh & Access token and cache them
		* @param Illuminate\Http\Request
		* @return String - refresh token
		**/
		public static function code_to_refresh_token(Request $request)
		{
			if(!$request->clientId || !$request->secretId || !$request->code)
			{
				$error = 'Either "clientId", "SecretId" or "code" parameter is missing.';

				return response()->json(['error' => $error]);
			}

			$payload = http_build_query([
									'code' 					=> $request->code,
									'grant_type' 		=> 'authorization_code'
								]);

			$headers = ['Host: oauth.yandex.com', 
									'Content-Type: application/x-www-form-urlencoded',
									"Authorization: Basic ".base64_encode("{$request->clientId}:{$request->secretId}")];

			$ch = curl_init("https://oauth.yandex.com/token");

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, urldecode($payload));


			if(!$res = curl_exec($ch))
			{
				$curl_error = curl_error($ch);

				curl_close($ch);

				return response()->json(['error' => $curl_error]);
			}
			else
			{
				if($obj_response = json_decode($res))
				{
					if(! isset($obj_response->refresh_token))
					{
						return response()->json(['error' => json_encode($res)]);
					}

					Cache::forever('yandex_disk_access_token', $obj_response->access_token);
					Cache::forever('yandex_disk_refresh_token', $obj_response->refresh_token);

					return response()->json(['refresh_token' => $obj_response->refresh_token]);
				}

				return response()->json(['error' => 'Wrong response from "YandexDisk::code_to_access_token" method']);
			}
		}


		public static function refresh_access_token()
		{
			/*
				POST /token HTTP/1.1
				Host: oauth.yandex.com
				Content-type: application/x-www-form-urlencoded
				Content-Length: <request body length>
				[Authorization: Basic <encoded string client_id:client_secret>]
			*/

			$refresh_token = cache('yandex_disk_refresh_token') ?? config('filehosts.yandex.refresh_token') ?? abort(404);

			$payload = http_build_query([
									'grant_type'	=> 'refresh_token',
									'refresh_token' => $refresh_token,
									'client_id' => config('filehosts.yandex.client_id'),
									'client_secret' => config('filehosts.yandex.secret_id')
								]);

			$headers = ['Host: oauth.yandex.com', 
									'Content-Type: application/x-www-form-urlencoded',
									"Authorization: Basic ".base64_encode(config('filehosts.yandex.client_id').':'.config('filehosts.yandex.secret_id'))];

			$ch = curl_init("https://oauth.yandex.com/token");

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, urldecode($payload));

			$response = curl_exec($ch);
			$error    = curl_error($ch);
			$errno 		= curl_errno($ch);

			if($errno)
			{
				return response()->json(['error' => $error]);
			}

			if($response = json_decode($response))
			{
				if(property_exists($response, 'access_token'))
				{
					Cache::put('yandex_disk_access_token', $response->access_token, now()->addSeconds($response->expires_in));

					Cache::forget('yandex_disk_refresh_token');

					Cache::forever('yandex_disk_refresh_token', $response->refresh_token);
					
					return $response->access_token;
				}
			}

			abort(403, __('Could not get an access token'));
		}



		public static function list_files(Request $request)
		{
			$access_token = Cache::get('yandex_disk_access_token') ?? Self::refresh_access_token();

			exists_or_abort($access_token, __('Missing access token for Yandex Disk API'));

			$headers = [
				"Accept" => "application/json",
				"Authorization" => "OAuth {$access_token}"
			];

 			$payload = 	[
										'limit' => $request->limit ?? 20,
										//'media_type' => 'compressed',
										'fields' => 'id,size,name,path,mime_type,modified,size'
									];

 			if($request->offset)
 			{
 				$payload['offset'] = (int)$request->offset;
 			}

 			if($request->keyword)
 			{
 				$payload['keyword'] = $request->keyword;
 			}

 			try
 			{
	 			$client = new Client(['verify' => false, 'headers' => $headers]);

	 			$payload = http_build_query($payload);

	 			$response = $client->get("https://cloud-api.yandex.net/v1/disk/resources/files?{$payload}");


	 			if($response->getStatusCode() === 200)
	 			{
	 				if($obj_response = json_decode($response->getBody()))
					{
						if($request->keyword)
						{
							$items = [];

							foreach($obj_response->items as $k => &$item)
							{
								if(preg_match("/{$request->keyword}/i", $item->name))
								{
									$item->id = $item->path;

									$items[] = $item;
								}
							}

							$obj_response->items  = $items;
							$obj_response->offset = null;
						}
						else
						{
							if(count($obj_response->items) < (int)$request->limit)
							{
								$obj_response->offset = null;
							}
							else
							{
								$obj_response->offset = (int)$request->offset + (int)$request->limit;
							}

							$items = [];

							foreach($obj_response->items as $k => &$item)
							{
								$item->id = $item->path;
								
								$items[] = $item;
							}

							$obj_response->items = $items;
						}

						return response()->json(['files_list' => $obj_response]);
					}
					else
					{
						return response()->json(['error' => 'Wrong response from "YandexDisk::list_files" method']);
					}		
	 			}
	 			else
	 			{
	 				return response()->json(['error' => $response->getStatusCode()]);
	 			}
 			}
 			catch(\Exception $e)
 			{
 				return response()->json(['error' => $e->getMessage()]);
 			}
		}



		public static function download(array $config)
		{
				$item_path	= $config['item_id'];
				$cache_id 	= $config['cache_id'];
				$expiry 		= $config['expiry'] ?? 86400;

				$temp_url = Temp_Direct_Url::where(['product_id' => $cache_id, "host" => "yandex"])->where('expiry', '>=', time())->first();

				if(!$temp_url)
				{
					$access_token = cache('yandex_disk_access_token');
	            
					exists_or_abort($access_token, __('Missing access token for Yandex Disk API'));

					$headers = ['Host: cloud-api.yandex.net', 
											'Content-Type: application/json',
											"Authorization: OAuth {$access_token}"];
		            
					$item_path = urlencode($item_path);
		            
					$ch = curl_init("https://cloud-api.yandex.net/v1/disk/resources/download?path={$item_path}");

					curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
					curl_setopt($ch, CURLOPT_HTTPGET, 1);
					
					$res = curl_exec($ch);
					$errno = curl_errno($ch);
					$error = curl_error($ch);
					
					curl_close($ch);
					
					if($errno)
					{				
						$curl_msg = curl_error($ch);

						exists_or_abort(null, $curl_msg);
					}

					if($obj_response = json_decode($res))
					{
						if(!isset($obj_response->href))
						{
							exists_or_abort(null, "{$res->error} - {$res->error_description}");
						}

						DB::delete("DELETE FROM temp_direct_urls WHERE product_id = ?", [$cache_id]);

						$temp_url = Temp_Direct_Url::create([
							"product_id" => $cache_id,
							"host" => "yandex",
							"url" => $obj_response->href,
							"expiry" => time()+$expiry,
						]);
					}
				}
				
				return redirect()->away($temp_url->url);
		}


		/**
		* Test Only
		* Upload files from url to yandex disk
		* @param String - $file_url : external file url
		* @param String - path to where the file will be copied
		* @return String - Url to track upload progression and status
		**/
		public static function upload($file_url, $to_path)
		{
			$access_token = cache('yandex_disk_access_token');

			exists_or_abort($access_token, __('Missing access token for Yandex Disk API'));

			$payload = http_build_query([
									'url'		=> $file_url,
									'path'	=> "disk:/{$to_path}"
								]);

			$headers = ['Host: cloud-api.yandex.net',
									'Content-Type: application/x-www-form-urlencoded',
									"Authorization: OAuth {$access_token}"];

			$ch = curl_init("https://cloud-api.yandex.net/v1/disk/resources/upload?{$payload}");

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_POST, 1);

			if(!$res = curl_exec($ch))
			{
				$curl_error = curl_error($ch);

				curl_close($ch);

				return response()->json(['error' => $curl_error]);
			}
			else
			{
				if($obj_response = json_decode($res))
				{
					return $obj_response->href;
				}

				abort(403, 'Wrong response from "YandexDisk::upload" method');
			}
		}


		// For Testing Only
		public static function track_upload_operation($operation_url)
		{
			$access_token = cache('yandex_disk_access_token');

			exists_or_abort($access_token, __('Missing access token for Yandex Disk API'));

			$headers = ['Host: cloud-api.yandex.net', 
									'Content-Type: application/json',
									"Authorization: OAuth {$access_token}"];

			$ch = curl_init(urldecode($operation_url));

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_HTTPGET, 1);
			
			if(!$res = curl_exec($ch))
			{				
				$curl_msg = curl_error($ch);

				curl_close($ch);

				exists_or_abort(null, $curl_msg);
			}

			if($obj_response = json_decode($res))
			{
				return $obj_response->status;
			}
		}
	}