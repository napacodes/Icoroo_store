<?php

	function auth_is_admin()
	{
		return preg_match('/^(superadmin|admin)$/i', request()->user()->role ?? null);
	}

	
	function user($prop = null)
	{
		return \Auth::check() ? ($prop ? request()->user()->$prop : request()->user()) : null;
	}


	function str_replace_adv(string $str, array $replace)
	{
		foreach($replace as $val)
		{
			if(!empty($val['src']))
			{
				$str = str_replace($val["search"], call_user_func($val['src'], $val["value"]), $str);
			}
			else
			{
				$str = str_replace($val["search"], $val["value"], $str);	
			}
		}

		return $str;
	}


	function decodeUnicodeCharacters($string)
	{
	    for($i=0; $i<10; $i++)
	    {
	        $string = mb_convert_encoding($string, 'ISO-8859-1', 'UTF-8');
	    }

	    return $string;
	}


	function array_walk_alt($iterable, $callback)
  {
    array_walk($iterable, $callback);

    return $iterable;
  }


  function array_keys_recursive($assoc_arr, &$result = [])
  {
  	foreach($assoc_arr as $key => $val)
  	{
  		$result[] = $key;

  		if(is_array($val) && array_is_associative($val))
  		{
  			array_keys_recursive($val, $result);
  		}
  	}
  }


  function array_is_associative($arr)
  {
      return count($arr) !== count(array_filter(array_keys($arr), fn($k) => is_int($k)));
  }


  function productHasGroupBuy($product, $init = true)
  {
  	$group_buy_min_buyers = \App\Models\User_Shopping_Cart_Item::where('item_id', $product->id)->count();

  	if(!isset($product->group_buy_price))
  	{
  		return false;
  	}
  	
  	if($product->group_buy_price > 0 && (!$product->group_buy_expiry || $product->group_buy_expiry > time()) && (!$product->group_buy_min_buyers || $init || $group_buy_min_buyers >= $product->group_buy_min_buyers))
    {
      return true;
    }

    return false;
  }


	function indexNow($url)
	{
		if(!$index_now_key = config("app.indexnow_key", env('INDEXNOW_KEY')))
		{
			return ['status' => 0, 'message' => __('Missing index now')];
		}

		$url = urlencode($url);

		$client = new \GuzzleHttp\Client(['verify' => false, 'http_errors' => false]);

		$response = $client->request("GET", "https://api.indexnow.org/indexnow?url={$url}&key={$index_now_key}");
		$status = $response->getStatusCode();

		if(in_array($status, [200, 202]))
		{
			return ['status' => true, 'message' => __("URL submitted to IndexNow successfully.")];
		}
		else
		{
			$errors = [
				400 => __("Invalid format"),
				403 => __("In case of key not valid (e.g. key not found, file found but key not in the file)"),
				422 => __("In case of URLs which don’t belong to the host or the key is not matching the schema in the protocol"),
				429 => __("Too Many Requests (potential Spam)"),
			];

			return ['status' => false, 'message' => ($errors[$status] ?? __("Unknow error"))];
		}
	}


	function generateQRCode()
	{
    $ga = new \PHPGangsta_GoogleAuthenticator();
    
    $secretKey = $ga->createSecret();

    $params = [
			'width' => 200,
			'height' => 200,
    ];

    $qrCodeUrl = $ga->getQRCodeGoogleUrl(config('app.name'), $secretKey, null, $params);

    return compact('qrCodeUrl', 'secretKey');
	}


	function verifyQRCode($db_two_factor_auth_secret, $two_factor_auth_code, $discrepancy = 1, $currentTimeSlice = null)
	{
		$ga = new \PHPGangsta_GoogleAuthenticator();
    
    return $ga->verifyCode($db_two_factor_auth_secret, $two_factor_auth_code, $discrepancy, $currentTimeSlice);
	}


	function randImage()
	{
		$images = array_map("basename", glob(public_path("menu/*.jpg")) ?? []);
		$img = $images[rand(0, (count($images) - 1))] ?? "";

		return asset_("menu/{$img}");
	}


	function logWebhookNotif($payment_processor)
	{	
		$time = time();

		file_put_contents(base_path("WEBHOOK_GET_RESPONSE_{$payment_processor}_{$time}.txt"), json_encode($_GET, JSON_PRETTY_PRINT));
		file_put_contents(base_path("WEBHOOK_POST_RESPONSE_{$payment_processor}_{$time}.txt"), json_encode($_POST, JSON_PRETTY_PRINT));
    file_put_contents(base_path("WEBHOOK_STDIN_RESPONSE_{$payment_processor}_{$time}.txt"), file_get_contents("php://input"));
    file_put_contents(base_path("WEBHOOK_HEADERS_RESPONSE_{$payment_processor}_{$time}.txt"), json_encode(getallheaders(), JSON_PRETTY_PRINT));
	}


	function bbcode_to_html($text, $nl2br = false, $charset = 'utf-8')
	{
	  $basic_bbcode = [
	    '[b]', '[/b]',
	    '[i]', '[/i]',
	    '[u]', '[/u]',
	    '[s]','[/s]',
	    '[ul]','[/ul]',
	    '[li]', '[/li]',
	    '[ol]', '[/ol]',
	    '[center]', '[/center]',
	    '[left]', '[/left]',
	    '[right]', '[/right]',
	  ];

	  $basic_html = [
	    '<b>', '</b>',
	    '<i>', '</i>',
	    '<u>', '</u>',
	    '<s>', '</s>',
	    '<ul>','</ul>',
	    '<li>','</li>',
	    '<ol>','</ol>',
	    '<div style="text-align: center;">', '</div>',
	    '<div style="text-align: left;">',   '</div>',
	          '<div style="text-align: right;">',  '</div>',
	  ];

	  $text = str_replace($basic_bbcode, $basic_html, $text);

	  $advanced_bbcode = [
	    '#\[color=([a-zA-Z]*|\#?[0-9a-fA-F]{6})](.+)\[/color\]#Usi',
	    '#\[size=([0-9][0-9]?)](.+)\[/size\]#Usi',
	    '#\[quote](\r\n)?(.+?)\[/quote]#si',
	    '#\[quote=(.*?)](\r\n)?(.+?)\[/quote]#si',
	    '#\[url](.+)\[/url]#Usi',
	    '#\[url=(.+)](.+)\[/url\]#Usi',
	    '#\[email]([\w\.\-]+@[a-zA-Z0-9\-]+\.?[a-zA-Z0-9\-]*\.\w{1,4})\[/email]#Usi',
	    '#\[email=([\w\.\-]+@[a-zA-Z0-9\-]+\.?[a-zA-Z0-9\-]*\.\w{1,4})](.+)\[/email]#Usi',
	    '#\[img](.+)\[/img]#Usi',
	    '#\[img=(.+)](.+)\[/img]#Usi',
	    '#\[code](\r\n)?(.+?)(\r\n)?\[/code]#si',
	    '#\[youtube]http://[a-z]{0,3}.youtube.com/watch\?v=([0-9a-zA-Z]{1,11})\[/youtube]#Usi',
	    '#\[youtube]([0-9a-zA-Z]{1,11})\[/youtube]#Usi'
	  ];

	  $advanced_html = [
	    '<span style="color: $1">$2</span>',
	    '<span style="font-size: $1px">$2</span>',
	    "<div class=\"quote\"><span class=\"quoteby\">Disse:</span>\r\n$2</div>",
	    "<div class=\"quote\"><span class=\"quoteby\">Disse <b>$1</b>:</span>\r\n$3</div>",
	    '<a rel="nofollow" target="_blank" href="$1">$1</a>',
	    '<a rel="nofollow" target="_blank" href="$1">$2</a>',
	    '<a href="mailto: $1">$1</a>',
	    '<a href="mailto: $1">$2</a>',
	    '<img src="$1" alt="$1" loading="lazy" />',
	    '<img src="$1" alt="$2" loading="lazy" />',
	    '<div class="code">$2</div>',
	    '<object type="application/x-shockwave-flash" style="width: 450px; height: 366px;" data="http://www.youtube.com/v/$1"><param name="movie" value="http://www.youtube.com/v/$1" /><param name="wmode" value="transparent" /></object>',
	    '<object type="application/x-shockwave-flash" style="width: 450px; height: 366px;" data="http://www.youtube.com/v/$1"><param name="movie" value="http://www.youtube.com/v/$1" /><param name="wmode" value="transparent" /></object>'
	  ];

	  $text = preg_replace($advanced_bbcode, $advanced_html,$text);

	  return $nl2br ? nl2br($text) : $text;
	}

	function if_null($str, $replacement)
	{
		if(is_null($str) || (strtolower((string)$str) === "null"))
			return $replacement;

		return $str;
	}

	function arrRandomEl($arr)
	{
		return $arr[rand(0, (count($arr)-1))];
	}


	function array_has_values($haystack, $needle, $all = false)
	{
		$haystack_values = array_map('strtolower', array_values($haystack));

		$found = 	array_filter($needle, function($v, $k) use($haystack_values)
							{
								return in_array(strtolower($v), $haystack_values);
							}, 
							ARRAY_FILTER_USE_BOTH);

		return $all ? (count($found) === count($needle)) : (bool)count($found);
	}



	function phpinfo_array()
	{
	    ob_start();
	    phpinfo();
	    $info_arr = array();
	    $info_lines = explode("\n", strip_tags(ob_get_clean(), "<tr><td><h2>"));
	    $cat = "General";
	    foreach($info_lines as $line)
	    {
	        preg_match("~<h2>(.*)</h2>~", $line, $title) ? $cat = $title[1] : null;
	        if(preg_match("~<tr><td[^>]+>([^<]*)</td><td[^>]+>([^<]*)</td></tr>~", $line, $val))
	        {
	            $info_arr[$cat][$val[1]] = $val[2];
	        }
	        elseif(preg_match("~<tr><td[^>]+>([^<]*)</td><td[^>]+>([^<]*)</td><td[^>]+>([^<]*)</td></tr>~", $line, $val))
	        {
	            $info_arr[$cat][$val[1]] = array("local" => $val[2], "master" => $val[3]);
	        }
	    }
	    return $info_arr;
	}



	function load_world_map($traffic = [])
	{
		$world_map = file_get_contents(public_path("assets/images/world_map.svg"));

	  $html = str_get_html($world_map);

	  foreach($html->find('[data-title]') as $element)
	  {
	    $country = $element->getAttribute("data-title");
	    $title   = $element->find('title', 0);

	    $title->innertext = __(mb_ucfirst($country)) .' : '. ($traffic[$country] ?? 0) . ' ' . __('Hits');
	  }

	  return $html->save();
	}



	function cover_mask($for = "homepage")
  {	
  	$request = request();
    $refresh = $request->query('refresh');
    $mask_file = public_path("storage/images/{$for}_bricks_cover.svg");

    if(!file_exists($mask_file) || ($refresh == 1 && auth_is_admin()))
    {
      $mask_file   = file_get_contents(public_path('storage/images/bricks-mask.svg'));
      $bricks_imgs = glob(public_path("storage/images/bricks/{$for}/*"));

      if($bricks_imgs)
      {
        shuffle($bricks_imgs);
        
        $bricks_imgs = array_slice($bricks_imgs, 0, 24);

        foreach($bricks_imgs as &$img) 
        {
          $img = basename($img);
          $img = file_get_contents(public_path("storage/images/bricks/{$for}/{$img}"));
        }
      }
      else
      {
    		$classes = [
    			"homepage" => "\App\Models\Product",
    			"blog"     => "\App\Models\Post",
    		];

    		$bricks_imgs = 	$classes[$for]::select('cover')->where('active', 1)->orderbyRaw('RAND()')
    										->limit(24)->get()->pluck('cover')->toArray();	
    	

        foreach($bricks_imgs as &$img) 
        {
          $img = file_get_contents(public_path("storage/cover/{$img}"));
        }
      }

      $default_img = file_get_contents(public_path("storage/covers/default.webp"));
      
      for($k = 0; $k <= 15; $k++)
      {
          $replace = $bricks_imgs[$k] ?? $default_img;

          $replace = base64_encode($replace);

          $mask_file = str_ireplace("{IMAGE_$k}", "data:image/png;base64, {$replace}", $mask_file);   
      }

      file_put_contents(public_path("storage/images/{$for}_bricks_cover.svg"), $mask_file);
    }

    echo asset("storage/images/{$for}_bricks_cover.svg");
  }



	function user_credits($recalculate = false, $property = null)
	{
		if(\Auth::check() && $recalculate)
		{
			$affiliate_credits = \App\Models\Affiliate_Earning::selectRaw("id, commission_value as credits, 0 as discount, 'affiliate' as type")
														->where(['referrer_id' => \Auth::id(), 'paid' => 0])
														->orderBy('updated_at', 'asc')
														->get();

			$prepaid_credits =  \App\Models\User_Prepaid_Credit::selectRaw("user_prepaid_credits.id, user_prepaid_credits.credits, 
														IFNULL(prepaid_credits.discount, 0) as discount, 'prepaid_credits' as type")
													->join("transactions", "transactions.id", "=", "user_prepaid_credits.transaction_id")
													->join('prepaid_credits', 'prepaid_credits.id', '=', 'user_prepaid_credits.prepaid_credits_id')
			                    ->where([
			                        'user_prepaid_credits.user_id' => \Auth::id(), 
			                        'transactions.status' => 'paid', 
			                        'transactions.confirmed' => 1,
			                        'transactions.refunded' => 0
			                    ])
			                    ->whereRaw('IF(? IS NOT NULL, NOW() <= DATE_ADD(user_prepaid_credits.updated_at, INTERVAL ? DAY), 1)', 
			                        array_fill(0, 2, config('app.prepaid_credits.expires_in', null)))
			                    ->orderBy('user_prepaid_credits.updated_at', 'asc')
			                    ->get();
 	
      $total_available_credits = 0;

      foreach([$affiliate_credits, $prepaid_credits] as $items)
      {
				$total_available_credits += $items->reduce(function($carry, $item)
	 			{
	 				$carry += $item->credits;
	 				return $carry;
	 			}, 0);      	
      }
      	
      $response = compact('total_available_credits', 'affiliate_credits', 'prepaid_credits');

			return $property ? ($response[$property] ?? null) : $response;
		}

		return config('app.prepaid_credits.enabled')
					 ? (config('prepaid_credits', 0) + config('affiliate_earnings', 0))
					 : config('affiliate_earnings', 0);
	}



	function itemHasVideo($item)
	{
		return $item->file_extension === "mp4" && $item->file_host !== "google" ? "mp4" : false;
	}


	function sendEmailMessage(array $config, $queued = true, $cli = false)
	{
		\Session::remove('sending_email');

		if(!session('sending_email'))
		{
			\Session::put('sending_email', true);

			extract($config);

			$reply_to = $reply_to ?? config('mail.reply_to');

			if(!$cli && config('mail.mailers.smtp.use_queue', $queued) && $action === 'send')
      {
        $queued_mails = cache('queued_mails', []);

        $config['action']   = 'send';
        $config['reply_to'] = $reply_to;

        $queued_mails[] = $config;

        \Cache::forever('queued_mails', $queued_mails);

        \Session::remove('sending_email');

        return;
      }

	    $response = \Mail::$action($view, $data, function($message) use($to, $subject, $reply_to)
	    {
	        $message = $message->bcc(is_array($to) ? array_filter($to) : trim($to));

	        if($reply_to)
	        {
	            $message->replyTo($reply_to);
	        }

	        $message->subject($subject);
	    });

	    \Session::remove('sending_email');

	    if($action === 'render')
	      exit($response);

	    return $response;
		}
	}


	function get_category_by_slug($category_slug, $subcategory_slug = null)
	{
		$categories = collect(config('categories.category_parents', []));
		$category   = $categories->where('slug', '=', $category_slug)->first();

		if($subcategory_slug)
		{
			if(!$category)
				return;

			$subcategories = collect(config("categories.category_children.{$category->id}", []));
			$subcategory   = $subcategories->where('slug', '=', $subcategory_slug)->first();

			return $subcategory;
		}

		return $category;
	}


	function userPageName($route_name)
	{
		$routes = [
			'home.collection' 				=> __('Collection'),
			'home.profile' 						=> __('Profile'),
			'home.user_subscriptions' =>  __('Memberships'),
			'home.purchases' 					=> __('Purchases'),
			'home.invoices' 					=> __('Invoices'),
			'home.notifications' 			=> __('Notifications'),
			'home.user_affiliate' 		=> __('Affiliate earnings')
		];

		return $routes[$route_name] ?? null;
	}


	function get_auto_increment($table)
	{
		\DB::statement("ANALYZE TABLE {$table}");
		
	  $statement = \DB::select("SHOW TABLE STATUS LIKE '$table'");
	  
	  return $statement[0]->Auto_increment ?? null;
	}


  function get_controller_action($get = null)
	{
	    $params = ['controller' => '', 'action' => ''];

	    if(preg_match('/(?P<controller_action>(\w+)Controller@(\w+))/i', \Route::currentRouteAction(), $matches))
	    {
	        $params = array_combine(array_keys($params), explode('@', $matches['controller_action']));
	    }
	    
	    if(preg_match('/^(controller|action)$/i', $get))
	    {
	        return $params[$get];
	    }
	    
	    return $params;
	}

	function json($arr)
	{
		return response()->json($arr);
	}


	function item_has_badge($item)
	{
	    $res = null;
	    
	    if($item->featured ?? null)
	        $res = 'featured';
	    
	    if($item->trending ?? null)
	        $res = 'trending';
	        
	   return $res;
	}
	

	function page_url($slug)
	{
		return route('home.page', ['slug' => $slug]);
	}


	function post_url($slug)
	{
		return route('home.post', ['slug' => $slug]);	
	}


	function wrap_str($str = '', $first_delimiter = "'", $last_delimiter = null)
	{
		if(!$last_delimiter)
		{
			return $first_delimiter.$str.$first_delimiter;
		}

		return $first_delimiter.$str.$last_delimiter;
	}


	function mb_ucfirst($str, $encoding = 'UTF-8')
  {
  	if(!$str) return;

    $strlen = mb_strlen($str, $encoding);
    
    if($strlen === 1)
    {
      return mb_strtoupper($str, $encoding);
    }
    
    return mb_strtoupper($str[0], $encoding) . mb_substr($str, 1, $strlen, $encoding);
  }


  function str_extract($str, $pattern, $get = null, $default = null)
  {
  	$result = [];

  	preg_match($pattern, $str, $matches);

  	preg_match_all('/(\(\?P\<(?P<name>.+)\>\.\+\)+)/U', $pattern, $captures);

  	$names = $captures['name'] ?? [];
  	
  	foreach($names as $name)
  	{
  		$result[$name] = $matches[$name] ?? null;
  	}

  	return $get ? ($result[$get] ?? $default) : $result;
  }


	function login($user, $remember = true)
	{
	  try
	  {
	    if(is_numeric($user))
	    {
	      \Auth::loginUsingId($user, $remember);
	    }
	    else
	    {
	      \Auth::login(filter_var($user, FILTER_VALIDATE_EMAIL) ? \App\Models\User::where('email', $user)->first() : $user, $remember);
	    }
	  }
	  catch(\Exception $e)
	  {
	    abort(403, $e->getMessage());
	  }
	}


	function updateCachedMainFiles($cache_id, $file_content, $expiry = 86400)
	{
		$files = \Cache::get('main_files', []);

		$data = ['url' => $file_content, 'expiry' => time()+$expiry];

		$files[$cache_id] = $data;
	
		\Cache::forever('main_files', $files);

		return $data;
	}




  // Add / Remove / Get pending transactions
  function pending_transactions($key = null, $value = null)
  {
  	try
  	{
	  	if(!is_array(config("pending_transactions")))
	  	{
	  		config(["pending_transactions" => []]);
	  	}

	  	if(!isset($key, $value))
	  	{
	  		return config("pending_transactions");
	  	}
			elseif(is_array($key))
			{
				return config(["pending_transactions.{$key[0]}" => $key[1]]);
			}
			else
			{
				if($value === "")
				{
					$pending_transactions = config("pending_transactions", []);
				
					$deleted_transaction = $pending_transactions[$key];

					unset($pending_transactions[$key]);

					config(compact('pending_transactions'));
				
					return [$key => $deleted_transaction];
				}
				elseif($value)
				{
					return config("pending_transactions.{$key}", $value);
				}
			}
  	}
  	catch(\Exception $e)
  	{
  		exists_or_abort(null, $e->getMessage());
  	}
  }



  function array_has_columns(array $array, array $columns)
  {
  	return count(array_diff_key($array, $columns)) > 0;
  }


	function array_columns($array = [], $columns = [])
	{
		$arr = [];

		if(! array_diff($columns, array_keys($array)))
		{
			foreach($columns as $column)
			{
				if(isset($array[$column]))
			    $arr[$column] = $array[$column];
			}

			return $arr;
		}
		else
		{
			foreach($array as $k => $v)
			{
				$arr[$k] = [];
				
				foreach($columns as $column)
				{
					if(isset($v[$column]))
				    $arr[$k][$column] = $v[$column];
				}
			}
		}
		
		return $arr;
	}


	function object_property($obj, $propery_name, $unique = false)
	{
		$arr = json_decode(json_encode($obj), true);

		$property = array_column($arr, $propery_name);
		
		return $unique ? array_unique($property) : $property;
	}


	function route_is(string $route_name)
	{
		return \Route::currentRouteName() === $route_name;
	}


	function route_slug()
	{
		return str_replace('.', '-', \Route::currentRouteName());
	}


	function view_($name, $data = [])
	{
		$template = config('app.template', 'default');

		return view("front.{$template}.{$name}", $data);
	}


	function view_path($name)
	{
		$template = config('app.template', 'default');

		return "front.{$template}.{$name}";
	}



	function shorten_str($string, $limit = 100)
	{
		return strlen($string) > $limit ? (mb_substr($string, 0, $limit).'...') : $string;
	}



	function replace_keys(array &$haystack, array $replacements)
	{
		foreach($replacements as $from => $to)
		{
			if(!isset($haystack[$from]))
				continue;
			
			$haystack[$to] = $haystack[$from];

			unset($haystack[$from]);
		}
	}
	


	function unwrap_str($str = '', $first_delimiter = "'", $last_delimiter = null)
	{
		if(!$last_delimiter)
		{
			return trim($str, $first_delimiter);
		}

		return rtrim(ltrim($str, $first_delimiter), $last_delimiter);
	}



	function prt($var, $exit = true)
	{
		echo '<pre>'.print_r($var, true).'</pre>';

		$exit ? exit : null;
	}


	function exchange_rate($to, $base = null)
	{
		$CurrencyExchanger = new \App\Libraries\CurrencyExchanger($to, $base);

    foreach(config('exchangers') as $method => $config)
    {
      $CurrencyExchanger->$method();
    }

    return $CurrencyExchanger;
	}


	function country_currency($ip = null)
	{
			$user_country      = user_country($ip);
			$user_country_code = $user_country->code ?? null;
			$user_currency = config("payments.country_currency.{$user_country_code}", config('payments.currency_code'));

			if(!config("payments.currencies.{$user_currency}"))
			{
				$user_currency = config('payments.currency_code');
			}		
			
			return $user_currency;		
	}


	function prepare_currency(&$ref)
	{
			$ref->currency_code = config('payments.currency_code');
			$ref->decimals      = config("payments.currencies.{$ref->currency_code}.decimals");
			
			if($admin_exchange_rate = session('admin_exchange_rate'))
			{
				$ref->exchange_rate = $admin_exchange_rate;
			}
			
			if($auto_currency = strtoupper(config("payments_gateways.{$ref->name}.auto_exchange_to")))
			{
				$ref->currency_code = $auto_currency;
			}
			elseif(config('payments.currency_by_country'))
			{
				$user_currency = country_currency();

				if($user_currency != $ref->currency_code)
				{
					$ref->currency_code = $user_currency; 
				}
			}
			elseif(session('currency'))
			{
				$ref->currency_code = session('currency');
			}


			if($ref->currency_code != config('payments.currency_code'))
			{
				if(is_array($ref->supported_currencies ?? null))
				{
					if(!in_array($ref->currency_code, $ref->supported_currencies))
					{
						$ref->error_msg = ['user_message' => __('Selected currency :currency_code not supported', ['currency_code' => $ref->currency_code])];
						
						return;
					}
				}

				$exchanger = exchange_rate($ref->currency_code);

				if(!$exchanger->status)
				{
					$ref->error_msg = ['user_message' => $exchanger->message];

					return;
				}

				$ref->exchange_rate = $exchanger->rate;
			}

			$ref->decimals = config("payments.currencies.{$ref->currency_code}.decimals", 2);
	}



	function find_one(array $arr_of_objs, array $props, string $search)
	{
	  foreach($arr_of_objs as $obj)
	  {
	    $_obj = $obj;
	    
	    foreach($props as $k => $prop)
	    {
	      if(property_exists($_obj, $prop))
	      {
	        $_obj = $_obj->$prop;
	        
	        if($_obj === $search)
	        {
	           return $obj;
	        }
	      }
	    }
	  }
	}

	function array_diff_recursive($array1, $array2) 
	{
	  $result = [];

	  foreach($array1 as $m_key => $m_value) 
	  {
	    if(array_key_exists($m_key, $array2)) 
	    {
	      if(is_array($m_value)) 
	      {
	        $recursive_diff = array_diff_recursive($m_value, $array2[$m_key]);

	        if(count($recursive_diff)) 
        	{ 
        		$result[$m_key] = $recursive_diff; 
        	}
	      } 
	      else 
	      {
	        if($m_value != $array2[$m_key]) 
	        {
	          $result[$m_key] = $m_value;
	        }
	      }
	    } 
	    else 
	    {
	      $result[$m_key] = $m_value;
	    }
	  }

	  return $result;
	}


	function prefix_arr_elements($arr, $prefix)
	{
		foreach($arr as &$val)
		{
			$val = $prefix.$val;
		}

		return $arr;
	}


	function user_country($ip = null, $property = null)
	{	
			$ip = $ip ?? request()->ip();

			try
			{
				if($ip === '127.0.0.1')
				{
					return;
				}

				$reader  = config('mmdb_reader');
				$country = $reader->country($ip)->country ?? null;

				if($property)
				{
					return $country->$property ?? null;
				}

				return (object)['code' => ($country->isoCode ?? null), 'name' => $country->names['en'] ?? null];
			}
			catch(\Exception $e)
			{

			}
	}


	function rand_subcategory($subcategories, $category = '')
	{
		$subcategories_arr = explode(',', $subcategories);
        
    if(!array_filter($subcategories_arr))
        return $category;

		return $subcategories_arr[rand(0, (count($subcategories_arr)-1))] ?? $category;
	}



	function rand_subcategory_2(string $subcategories = null, string $category_slug, string $category_name)
	{
		$parent = ['category_slug' => $category_slug];

		if($subcategories = array_filter(explode(',', $subcategories)))
		{
			$subcategory = $subcategories[rand(0, (count($subcategories)-1))];
			$url = route('home.products.category', array_merge($parent, ['subcategory_slug' => \Str::slug($subcategory)]));

			return '<a class="subcategory capitalize" href="'. $url .'">'. $subcategory .'</a>';
		}
		else
		{
			$url = route('home.products.category', $parent);
			return '<a class="capitalize" href="'. $url .'">'. $category_name .'</a>';
		}

		return;
	}



	function slug($title, $separator = '-', $language = 'en')
	{
		return \Illuminate\Support\Str::slug($title, $separator, $language);
	}


	function currency($val = 'code')
	{
		if(config('payments.currencies'))
		{
			$currency = config("payments.currency_code");

			return 	$val === 'code' 
							? session('currency', $currency)
							: config('payments.currencies.'.session('currency', $currency).'.symbol');
		}

		return config("payments.currency_{$val}") ?? config("payments.currency_code");		
	}


	function debug()
	{
		$numargs = func_num_args();

		if($numargs < 2)
		{
			dd('debug() function takes at least 2 arguments where the 1st is the ip address.');
		}

		if(request()->ip() == func_get_arg(0))
		{
			$arg_list = func_get_args();
			$arg_list = array_slice($arg_list, 1);

			dd(...$arg_list);
		}
	}


	function price($price, $free = true, $with_decimals = true, $decimals = 2, $currency = 'symbol', $k = true, 
								 $cust_currency = null, $supcurrency = false)
	{
		$k = config('payments.show_prices_in_k_format');
		
		if(!$price && $free) return __('Free');
		
		$separator = strtolower($currency) === 'code' ? ' ' : '';
		$currency  = $cust_currency ?? ($currency ? currency($currency) : null);
		
		if($rate = config('payments.exchange_rate'))
		{
			$price = (float)$price * (float)$rate;

			if($currency_code = session('currency'))
			{
				$decimals = config("payments.currencies.{$currency_code}.decimals");
			}
		}
		
		$price = ($with_decimals ? number_format($price, $decimals, '.', '') : $price);

		if(preg_match('/^BTC|₿$/i', $currency))
		{
			$price = $price > 0 ? rtrim($price, '0') : number_format($price, 2, '.', '');
		}

		$price = ($price > 1000 && $k) ? ($with_decimals ? number_format($price/1000, $decimals, '.', '') : $price).__('K') : $price;

		$currency_position = config('payments.currency_position', 'left');

		$currency = $supcurrency ? "<sup>{$currency}</sup>" : $currency;

		return ($currency_position === 'left') ? ($currency . $separator . $price) : ($price . $separator . $currency);
	}


	function exchange_rate_required()
	{
		return config('payments.allow_foreign_currencies') && config('payments.exchanger') && 
      	 (session('currency') && strtolower(session('currency')) !== strtolower(config('payments.currency_code')));
	}



	function get_decimals($currency_code = null)
	{
		$currency_code = $currency_code ?? session('currency') ?? currency('code');

		return config("payments.currencies.{$currency_code}.decimals");
	}


	function convert_amount($amount, $currency = null)
	{
		$base_currency = config('payment.currency_code');
		$user_currency = $currency ?? session('currency') ?? config('payments.user_base_currency');
		$exchange_rate = config('payments.exchange_rate', 1);

		if($user_currency && ($base_currency !== $user_currency) && !config('payments.user_base_currency'))
		{
			$currency_config = config("payments.currencies.{$user_currency}.exchange_rate", $exchange_rate);

			$amount = $amount * config("payments.currencies.{$user_currency}.exchange_rate", $exchange_rate);

			return format_amount($amount, false, config("payments.currencies.{$user_currency}.decimals"));
		}

		return format_amount($amount, false, config("payments.currencies.{$base_currency}.decimals"));
	}



	function format_amount($number, $use_default = true, $decimals = 2)
	{
		if($use_default)
		{
			$currency = config('payments.currency_code');
			$decimals = config("payments.currencies.{$currency}.decimals");
		}

		return number_format($number, $decimals ?? 2, '.', '');
	}



  function exists_or_abort($variable, $error_msg = '')
	{
		if(!isset($variable))
		{
			if(config('app.env') === 'local')
			{
				abort(403, $error_msg);
			}

			abort(404);
		}
	}



	function out_of_stock($item, $out_of_stock_str = false)
	{
		if(is_numeric($item->stock) && $item->stock == 0 || ($item->has_keys > 0 && $item->remaining_keys == 0))
		{
			return $out_of_stock_str ? 'out-of-stock' : true;
		}
		else
		{
			return false;
		}
	}

	function item_is($item, $item_type)
	{
		return $item->item === $item_type;
	}


	function has_promo_time($item, $str = false)
	{
		if($item->promotional_price_time && $item->promotional_price)
		{
			return $str ? 'in-promo' : true;
		}

		return false;
	}


	function has_promo_price($item, $str = false)
	{
		if($item->promotional_price)
		{
			return $str ? 'in-promo' : true;
		}

		return false;
	}




	function item_url($item)
	{
		if(is_object($item))
		{
			$params = ['id' => $item->id, 'slug' => $item->slug];
		}
		elseif(is_array($item))
		{
			$params = ['id' => $item['id'], 'slug' => $item['slug']];
		}
		else
		{
			$params = array_combine(['id', 'slug'], $item);
		}

		return route('home.product', $params);
	}


	function item_folder_sync($item)
	{
		return route('home.product_folder_sync', ['id' => $item->id, 'slug' => $item->slug]);
	}



	function url_append($array): string
	{
		return url()->current().'?'.http_build_query($array);
	}

	function url_params(string $param = null, string $value = null)
	{
		if($param)
		{
			return config("app.url_params.{$param}", []);
		}

		if($param && $value)
		{
			config(["app.url_params.{$param}" => $value]);
		}

		return config('app.url_params', []);
	}


	
	function auth_is_affiliate($user = null): bool
	{
		$user = $user ?? (\Auth::check() ? request()->user() : null);

		if(!isset($user->affiliate_name))
		{
			return false;
		}

		return mb_strlen($user->affiliate_name) > 0;
	}


	function category_url($category_slug, $subcategory_slug = null)
	{
		$params = ['category_slug' => $category_slug];

		if($subcategory_slug)
		{
			$params['subcategory_slug'] = $subcategory_slug;
		}

		/*if(request()->query())
		{
			$params[] = ($subcategory_slug ? '' : '?') . http_build_query(request()->query());
		}*/

		return route('home.products.category', $params);
	}


	function country_url($country)
	{
		$url_params = url_params();

		unset($url_params['cities']);

		$url_params['country'] = $country;

		return url()->current() . '?' . http_build_query($url_params);
	}

	function _sort($arr, $options = null)
	{
		sort($arr);

		return $arr;
	}

	function generate_transaction_ref($length = 12)
	{
		if(!$base = session('base_ref'))
    {
    	$refs = \DB::select("SELECT reference_id FROM transactions");
    	$base = array_column($refs, 'reference_id');
    	
      session(['base_ref' => $base]);
    }

    $length = ($length > 40) ? 12 : $length;

    $arr = array_merge(range('A', 'Z'), range(0, 9));
  
    shuffle($arr);
    
    $ref = implode('', array_slice($arr, 0, $length));

    while(in_array($ref, $base))
    {
      generate_ref($base, $length);
    }

    \Session::forget('base_ref');

    return $ref;
	}


	function shuffle_array($array)
	{
		shuffle($array);
		
		return $array;	
	}


	function uuid6()
	{
		return (\Ramsey\Uuid\Uuid::uuid4())->toString();
	}


	function tag_url($tag)
	{
		$tag   = mb_strtolower($tag);
		$query = request()->query();

		if($tags = request()->query('tags'))
		{
			$tags = explode(',', $tags);

			if(in_array($tag, $tags))
			{
				unset($tags[array_search($tag, $tags)]);
			}
			else
			{
				$tags = array_unique(array_merge($tags, [$tag]));
			}

			$query['tags'] = implode(',', $tags);
		}
		else
		{
			$query['tags'] = $tag;
		}

		if(empty($query['tags']))
			unset($query['tags']);

		$base_url = route_is('home.product') ? route('home.products.q') : url()->current();
		return $base_url.'?'. urldecode(http_build_query($query));
	}


	function tag_is_selected($tag)
	{
		if($tags = request()->query('tags'))
		{
			$tag  = mb_strtolower($tag);
			$tags = explode(',', $tags);

			return in_array($tag, $tags);
		}
	}


	function filter_url($filter)
	{
		$query = request()->query();

		if($sort = mb_strtolower(request()->query('sort')))
		{
			if($filter === $sort)
			{
				unset($query['sort']);
			}
		}

		$query['sort'] = $filter;

		return url()->current().'?'. urldecode(http_build_query($query));
	}


	function filter_is_selected($filter)
	{
		if($sort = request()->query('sort'))
		{
			if(is_array($filter))
			{
				return in_array($sort, $filter);
			}

			return $sort === $filter;
		}
	}


	function reset_filters()
	{
		if($q = request()->query('q'))
		{
			return url()->current() . (request()->category_slug ? '' : '?'.urldecode(http_build_query(['q' => $q])));
		}
		
		return url()->current();
	}


	function priceRange($key)
	{
		if($price_range = request()->query('price_range'))
		{
			$range = array_combine(['min', 'max'], explode(',', urldecode($price_range)));
			
			return $range[$key];
		}
	}

	function percent_off($price, $promo_price)
	{
		return @ceil((($price - $promo_price) / $price) * 100);
	}


	function blog_category_url($category_slug)
	{
		return route('home.blog.category', ['category' => $category_slug]);
	}



	function pricing_plan_url($plan)
	{
		return route('home.checkout', ['id' => $plan->id, 'slug' => $plan->slug, 'type' => 'subscription']);
	}


	function obj2arr($obj, $opt = [JSON_UNESCAPED_UNICODE])
	{
		return json_decode(json_encode($obj, ...$opt), true) ?? [];
	}


	function arr2obj($arr, $opt = [JSON_UNESCAPED_UNICODE])
	{
		return json_decode(json_encode($arr, ...$opt)) ?? [];
	}


	function display_counters(): bool
	{
		foreach(config('app.counters', []) as $name => $count)
		{
			if(!is_null($count))
				return true;
		}

		return false;
	}


	function format_date($date, $format)
	{
		if(is_numeric($date))
		{
			$date = (new \DateTime())->setTimestamp($date);

			return $date->format($format);
		}
		elseif(is_null($date))
		{
			return;
		}

		return (new \DateTime($date))->format($format);
	}


	function delete_cached_view($view_path)
	{
		$file_name = sha1('v2'. resource_path("views/{$view_path}")).'.php';
		$file_path = storage_path("framework/views/{$file_name}");

		if(is_file($file_path))
		{
			\File::delete($file_path);
		}
	}
	

	function generate_app_key($cipher = 'AES-256-CBC')
	{
		$key = 	'base64:'.base64_encode(
            	\Illuminate\Encryption\Encrypter::generateKey($cipher ?? config('app.cipher'))
        		);

		$app_key_pattern = preg_quote('='.config('app.key'), '/');

    $app_key_pattern = "/^APP_KEY{$app_key_pattern}/m";

		return 	file_put_contents(base_path('.env'), preg_replace(
	            $app_key_pattern,
	            'APP_KEY='.$key,
	            file_get_contents(base_path('.env'))
	        	));
	}

	function is_superadmin()
	{   
	    $user_name = "Guest";

	    if($user_name != 'superadmin')
	    {
	        \Validator::make(['name' => $user_name], [
                'name' => 'required|in:superadmin'
            ], ['in' => 'Action not allowed in demo version.'])->validate();
	    }
	}
	

	function cards_args($view, $iterable, $var_name, $attributes = [])
	{
		return json_encode(compact('view', 'iterable', 'var_name', 'attributes'));
	}


	function isUrl($str)
	{
		return filter_var($str, FILTER_VALIDATE_URL);
	}


	function cards(...$args)
	{
	    $args = func_get_args();

	    $view       = $args[0];
	    $iterable   = $args[1];
	    $var_name   = $args[2];
	    $attributes = $args[3] ?? [];
			
	    $content = '';
	    $i = 1;

	    foreach($iterable as $item)
	    {
	      $content .= view("components.{$view}", array_merge([$var_name => $item], $attributes, ['iteration' => $i]))->render();
	    	$i++;
	    }

	    echo $content;
	}

	
	function is_superuser()
	{
	    $user_name = request()->user()->name ?? null;
	    
	    if($user_name != 'superuser')
	    {
	        \Validator::make(['name' => $user_name], [
                'name' => 'required|in:superuser'
            ], ['in' => 'Action not allowed in demo version.'])->validate();
	    }
	}



	function asset_($path = null)
	{
		if(! $path) return;
		
		$path = trim($path, '/');

		$asset_param = env_is('local') ? ('?v=' . config('app.version') . '&t='.time()) : ('?v=' . config('app.version'));

		return "//{$_SERVER['HTTP_HOST']}/{$path}{$asset_param}";
	}



	function locale_direction()
	{
			return \LaravelLocalization::getCurrentLocaleDirection() ?? 'ltr';
	}

	function get_locale()
	{
		$locale = \LaravelLocalization::getCurrentLocale();
		$supported_locales = \LaravelLocalization::getSupportedLocales();

		return $supported_locales[$locale]['regional'] ?? $locale;
	}


	function load_font()
	{
		$direction = locale_direction();

		if($font = urldecode(config("app.fonts.{$direction}", null)))
		{
			if(filter_var(filter_var($font, FILTER_SANITIZE_URL), FILTER_VALIDATE_URL))
			{
					$client 	= new \GuzzleHttp\Client(['verify' => false, 'timeout' => 5, 'http_errors' => false]);

					$response = $client->get($font);

					if($response->getStatusCode() === 200)
					{
						$font = $response->getBody();
					}
			}

			return preg_replace("/font-family: '(.*)';?/i", 'font-family: Valexa;', $font);
		}
	}


	function file_uploaded($path, $id)
	{
		$files = File::glob(base_path("{$path}/{$id}.*"));

		if($files[0] ?? null)
		{
			return pathinfo($files[0], PATHINFO_BASENAME);
		}
	}


	function get_main_file($file_name)
	{
		return storage_path("app/downloads/{$file_name}");
	}


	function env_is(string $value): bool 
	{
		return strtolower(config('app.env')) === strtolower($value);
	}


	function update_env_var($var_name, $new_value = null)
	{
		$env =  array_reduce(file(base_path('.env'), FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES), 
	          function($carry, $item)
	          {
	          	$item = explode('=', $item, 2);

	          	$key = $item[0];
	          	$val = $item[1] ?? null;

	            $carry[$key] = $val;

	            return $carry;
	          }, []);

		if(is_array($var_name))
		{
			$env = array_merge($env, $var_name);
		}
		else
		{
			$env[$var_name] = $new_value;
		}

	  foreach($env as $k => &$v)
	  {
	  	$v = "{$k}={$v}";
	  }

	  return file_put_contents(base_path('.env'), implode("\r\n", $env));
	}



	function item_rating($rating = 0, $color1 = '#ffbd29', $color2 = '#d1d3d4')
	{
		$html = '';

		for($i = 1; $i < 6; $i++)
		{
			$color = $rating >= $i ? $color1 : $color2;

			$html .= <<< ICON
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 62.01">
								  <g id="icon">
								    <path d="M34,0c5.49,0,4.66,18.54,7.4,20.92S63.05,22.66,64,27.29,49.25,35.75,47.68,39.46,55,58.86,51.75,61.64,36.05,50.86,32,50.86,15.78,63.62,12.51,61.24s4.86-18.57,3.81-22S-.95,31.8,0,27.29s19.17-3.85,22.18-6.1S28.43.13,33,0Z" style="fill: {$color}; fill-rule: evenodd"/>
								  </g>
								</svg>
ICON;
		}

		return $html;
	}


	/**
	* @param $name String : responsive_ad|auto_ad|in_feed_ad|link_ad|ad_728x90|ad_468x60|ad_300x250|ad_320x100|popup_ad
	* @return String : ad code
	**/
	function place_ad($name)
	{
		$template = '<div class="ad [name]">[code]</div>';
		$ad_code  = '';

		if(preg_match('/ad_\d+x\d+/i', $name))
		{
			if($code = config('adverts.responsive_ad'))
			{
				$ad_code = str_ireplace(['[name]', '[code]'], ['responsive_ad', $code], $template);
			}
			else
			{
				if($name !== 'ad_300x250')
				{
					$ads = ['ad_320x100', 'ad_468x60', 'ad_728x90'];

					$index = array_search($name, $ads);

					for($i = $index; $i >= 0; $i--)
					{
						if($code = config("adverts.{$ads[$i]}"))
						{
							$ad_code .= str_ireplace(['[name]', '[code]'], [$ads[$i], $code], $template);
						}
					}
				}
				else
				{
					$ad_code = str_ireplace(['[name]', '[code]'], [$name, config("adverts.{$name}")], $template);
				}	
			}
		}
		else
		{
			$ad_code = config("adverts.{$name}");
		}

		return $ad_code;
	}


	function preview($item)
	{
		$preview   = $item->preview;
		$extension = strtolower(pathinfo($preview, PATHINFO_EXTENSION));

		if(!$extension && ($item->preview_type === 'video')) // embed link
    {
    		return '<iframe class="video" src="'.$preview.'" frameborder="0" allowfullscreen></iframe>';	
    }
    else
    {
	    	if($item->preview_type === 'video')
	    	{
	    		$url = preg_match('/https?|www/i', $preview) ? $preview : asset_("storage/previews/{$preview}");

	    		$poster = $item->type_is('graphic') ? '' : 'poster="'. asset_("storage/covers/{$item->cover}") .'"';
	    		$url    = $item->type_is('graphic') ? ($url.'#t=1') : $url;

	    		return '<video src="'. $url .'" '. $poster .' muted="muted" preload="metadata" type="video/'. $extension .'"></video>';	
	    	}
	    	elseif($item->preview_type === 'audio')
	    	{
	    		$player = <<< HTML
					<div class="audio-container" data-src=":src" data-id=":id">
						<div class="player">
							<div class="actions">
								<i class="play circle outline icon link mx-0 visible"></i>
								<i class="pause circle outline icon link mx-0"></i>
								<i class="stop circle outline link icon mx-0"></i>
							</div>
							<div class="timeline"><div class="wave"></div></div>
							<div class="duration">00:00</div>
						</div>
					</div>
HTML;

					return str_ireplace([':src', ':cover', ':id'], [preg_match('/(http|www)/i', $preview) ? $preview : asset_("storage/previews/{$preview}"), asset_("storage/covers/{$item->cover}"), $item->id], $player);
    	}
    }
	}



	function preview_link($item)
	{
		return preg_match('/(http|www)/i', $item->preview) ? $item->preview : asset_("storage/previews/{$item->preview}");
	}


	function adjustBrightness($hexCode, $adjustPercent) 
	{
	  $hexCode = ltrim($hexCode, '#');

	  if (strlen($hexCode) == 3) 
	  {
	      $hexCode = $hexCode[0] . $hexCode[0] . $hexCode[1] . $hexCode[1] . $hexCode[2] . $hexCode[2];
	  }

	  $hexCode = array_map('hexdec', str_split($hexCode, 2));

	  foreach ($hexCode as & $color) 
	  {
	      $adjustableLimit = $adjustPercent < 0 ? $color : 255 - $color;
	      $adjustAmount = ceil($adjustableLimit * $adjustPercent);

	      $color = str_pad(dechex($color + $adjustAmount), 2, '0', STR_PAD_LEFT);
	  }

	  return '#' . implode($hexCode);
	}


	function getEmailVerificationURL(User $notifiable)
	{
		$VerifyEmail = new \Illuminate\Auth\Notifications\VerifyEmail();
    $notifiable = User::where('email', 'foxtinez@gmail.com')->first();

	  return \URL::temporarySignedRoute(
        'verification.verify',
        \Illuminate\Support\Carbon::now()->addMinutes(\Config::get('auth.verification.expire', 60)),
        [
            'id' => $notifiable->getKey(),
            'hash' => sha1($notifiable->getEmailForVerification()),
        ]
    );
	}


	function share_link($name, $permalink = null, $title = null)
	{

		if($permalink && config('app.permalink_url_identifer'))
		{
			$item_url = route('product_with_permalink', ['slug' => $permalink]);
		}
		else
		{
			$item_url = url()->current();
		}

		if(\Auth::check())
		{
			if($affiliate_name = mb_strtolower(request()->user()->affiliate_name))
			{
				$item_url .= "?r={$affiliate_name}";
			}
		}

		$item_url = urlencode($item_url);

		$media = [
			'pinterest' => "https://www.pinterest.com/pin/create/button/?url={$item_url}",
			'twitter' => "https://twitter.com/intent/tweet?&url={$item_url}",
			'facebook' => "https://facebook.com/sharer.php?u={$item_url}",
			'tumblr' => "https://www.tumblr.com/widgets/share/tool?canonicalUrl={$item_url}",
			'vk' => "https://vk.com/share.php?url={$item_url}",
			'linkedin' => "https://www.linkedin.com/cws/share?url={$item_url}",
			'reddit' => "https://www.reddit.com/submit?title=".urlencode($title)."&selftext=true&url={$item_url}",
			'okru' => "https://connect.ok.ru/dk?st.cmd=WidgetSharePreview&st.shareUrl={$item_url}",
			'skype' => "https://web.skype.com/share?url={$item_url}&text=".urlencode($title),
			'telegram' => "https://t.me/share/url?url={$item_url}&text=".urlencode($title),
			'whatsapp' => "https://api.whatsapp.com/send?text=".urlencode($title)."%20{$item_url}",
		];

		return $media[$name] ?? null;
	}


	function render_captcha()
	{
		if($catcha_name = captcha_is_enabled())
		{
			if($catcha_name === 'google')
			{
				$captcha = google_captcha_instance();

				return $captcha->display(config('captcha.attributes'));
			}
			else
			{
				return \Mews\Captcha\Facades\Captcha::img('default', ['class' => 'circular-corner']);
			}
		}
	}


	function captcha_is_enabled($in = null)
	{
		if(!(config('captcha.enabled') || config('captcha.default.enabled')))
			return;

		$captcha = config('captcha.enabled') ? 'google' : (config('captcha.default.enabled') ? 'mewebstudio' : null);

		if(!$in)
			return $captcha;

		return in_array($in, config('captcha.enable_on', [])) && $captcha;
	}


	function captcha_is($name)
	{
		return $name === captcha_is_enabled();
	}
	

	function google_captcha_instance()
	{
		return new \Anhskohbo\NoCaptcha\NoCaptcha(config('captcha.secret'), config('captcha.sitekey'));
	}


	function google_captcha_js()
	{
		return (google_captcha_instance())->renderJs();
	}


	function remove_nl(string $string)
	{
		return preg_replace('/(\r\n)+|(\r+)|(\n+)/i', ' ', $string);
	}


	function is_single_prdcts_type()
	{
		return count(config('products_types', [])) === 1 ? (config('products_types', [])[0] ?? null) : false;
	}


	function item_has_info($item, array $info_columns)
	{
		foreach($info_columns as $column)
		{
			if($item->$column ?? null) 
			{
				return true;
				break;
			}
		}
	}


	function parse_css_selectors($css, $media_queries = true)
	{
	    $result = $media_blocks = [];

	    //---------------parse css media queries------------------

	    if($media_queries==true){

	        $media_blocks=parse_css_media_queries($css);
	    }

	    if(!empty($media_blocks)){

	        //---------------get css blocks-----------------

	        $css_blocks=$css;

	        foreach($media_blocks as $media_block){

	            $css_blocks=str_ireplace($media_block,'~£&#'.$media_block.'~£&#',$css_blocks);
	        }

	        $css_blocks=explode('~£&#',$css_blocks);

	        //---------------parse css blocks-----------------

	        $b=0;

	        foreach($css_blocks as $css_block){

	            preg_match('/(\@media[^\{]+)\{(.*)\}\s+/ims',$css_block,$block);

	            if(isset($block[2])&&!empty($block[2])){

	                $result[$block[1]]=parse_css_selectors($block[2],false);
	            }
	            else{

	                $result[$b]=parse_css_selectors($css_block,false);
	            }

	            ++$b;
	        }
	    }
	    else{

	        //---------------escape base64 images------------------

	        $css=preg_replace('/(data\:[^;]+);/i','$1~£&#',$css);

	        //---------------parse css selectors------------------

	        preg_match_all('/([^\{\}]+)\{([^\}]*)\}/ims', $css, $arr);

	        foreach ($arr[0] as $i => $x){

	            $selector = trim($arr[1][$i]);

	            $rules = explode(';', trim($arr[2][$i]));

	            $rules_arr = [];

	            foreach($rules as $strRule){

	                if(!empty($strRule)){

	                    $rule = explode(":", $strRule,2);

	                    if(isset($rule[1])){

	                        $rules_arr[trim($rule[0])] = str_replace('~£&#',';',trim($rule[1]));
	                    }
	                    else{
	                        //debug
	                    }
	                }
	            }

	            $selectors = explode(',', trim($selector));

	            foreach ($selectors as $strSel){

	                if($media_queries===true){

	                    $result[$b][$strSel] = $rules_arr;
	                }
	                else{

	                    $result[$strSel] = $rules_arr;
	                }
	            }
	        }
	    }
	    return $result;
	}

	function parse_css_media_queries($css)
	{

	    $mediaBlocks = array();

	    $start = 0;
	    while(($start = strpos($css, "@media", $start)) !== false){

	        // stack to manage brackets
	        $s = array();

	        // get the first opening bracket
	        $i = strpos($css, "{", $start);

	        // if $i is false, then there is probably a css syntax error
	        if ($i !== false){

	            // push bracket onto stack
	            array_push($s, $css[$i]);

	            // move past first bracket
	            $i++;

	            while (!empty($s)){

	                // if the character is an opening bracket, push it onto the stack, otherwise pop the stack
	                if ($css[$i] == "{"){

	                    array_push($s, "{");
	                }
	                elseif ($css[$i] == "}"){

	                    array_pop($s);
	                }

	                $i++;
	            }

	            // cut the media block out of the css and store
	            $mediaBlocks[] = substr($css, $start, ($i + 1) - $start);

	            // set the new $start to the end of the block
	            $start = $i;
	        }
	    }

	    return $mediaBlocks;
	}

	function number_to_word($number)
	{
		return (new \NumberFormatter("en", \NumberFormatter::SPELLOUT))->format($number);
	}


	function cache_exists($dir)
	{
		if(is_dir(storage_path("framework/{$dir}")))
		{
			return count(\File::allFiles(storage_path("framework/{$dir}"))) >= 1;
		}
	}


	function sitemap($type)
  {
    $urls = '';

    $sitemap = <<<XML
    <?xml version="1.0" encoding="UTF-8"?>
      <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
        [URLS]
      </urlset>
    XML;

    if($type === 'products')
    {
    	$products = \App\Models\Product::where('active', 1)->get();
  
	    foreach($products as $product)
	    {
	        $loc = item_url($product);

	        $urls .= <<<URL
					<url>
						<loc>{$loc}</loc>
						<changefreq>monthly</changefreq>
						<priority>0.8</priority>
					</url>
					URL;
	    }
    }
    elseif($type === 'posts')
    {
    	$posts = \App\Models\Post::where('active', 1)->get();

      foreach($posts as $post)
      {
          $loc = route('home.post', ['slug' => $post->slug]);

          $urls .= <<<URL
          <url>
            <loc>{$loc}</loc>
          </url>
          URL.PHP_EOL;
      }
    }
    elseif($type === 'pages')
    {
    	$pages = \App\Models\Page::where('active', 1)->get();

      foreach($pages as $page)
      {
          $loc = route('home.page', ['slug' => $page->slug]);

          $urls .= <<<URL
          <url>
            <loc>{$loc}</loc>
          </url>
          URL.PHP_EOL;
      }
    }
    elseif($type === 'index')
    {
    	$products = asset('sitemap_products.xml');
      $pages    = asset('sitemap_pages.xml');
      $posts    = asset('sitemap_posts.xml');

			$sitemap = <<<SITEMAPINDEX
			        <sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
						    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
						    xmlns:xhtml="http://www.w3.org/1999/xhtml"
						    xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
						    http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd
						    http://www.w3.org/1999/xhtml
						    http://www.w3.org/2002/08/xhtml/xhtml1-strict.xsd">
			          <sitemap>
			            <loc>{$products}</loc>
			          </sitemap>
			          <sitemap>
			            <loc>{$posts}</loc>
			          </sitemap>
			          <sitemap>
			            <loc>{$pages}</loc>
			          </sitemap>
			        </sitemapindex>
			SITEMAPINDEX;
    }
    else
    {
    	$products = \App\Models\Product::where('active', 1)->get();
  
	    foreach($products as $product)
	    {
	        $loc = item_url($product);

	        $urls .= <<<URL
					<url>
						<loc>{$loc}</loc>
						<changefreq>monthly</changefreq>
						<priority>0.8</priority>
					</url>
					URL;
	    }
    }

    return trim(str_ireplace('[URLS]', $urls, $sitemap));
  };


  function config($key = null, $default = null)
  {
      if (is_null($key)) {
          return app('config');
      }

      if (is_array($key)) {
          return app('config')->set($key);
      }

      return app('config')->get($key) ?? $default;
  }

  function get_url_param(string $url, string $param)
  {
  	parse_str(parse_url($url, PHP_URL_QUERY), $params);

  	return $params[$param] ?? null;
  }


	function cache()
  {
      $arguments = func_get_args();

      if (empty($arguments)) {
          return app('cache');
      }

      if (is_string($arguments[0])) {
      	$dot_value = array_filter(explode('.', $arguments[0], 2));

      	if(count($dot_value) > 1)
      	{
      		$arr = app('cache')->get($dot_value[0]);

      		return \Arr::get($arr, $dot_value[1]) ?? $arguments[1] ?? null;
      	}

        return app('cache')->get(...$arguments);
      }

      if (! is_array($arguments[0])) {
          throw new Exception(
              'When setting a value in the cache, you must pass an array of key / value pairs.'
          );
      }

      return app('cache')->put(key($arguments[0]), reset($arguments[0]), $arguments[1] ?? null);
  }


  function default_currency()
  {
  	try
  	{
	  	$payment_sets = json_decode(\App\Models\Setting::select('payments')->pluck('payments')->first());
	  	
	  	return $payment_sets->currency_code;	
  	}
  	catch(\Exception $e)
  	{

  	}	
  }

  function get_remote_file_content(string $remote_file_url, $file_name = null)
  {
      $content = null;
      $error   = null;
      $headers = config('filehosts.remote_files.headers', []);
      $body    = config('filehosts.remote_files.body', []);

      try
      {      	
        $client   = new \GuzzleHttp\Client(['headers' => $headers, 'form_params' => $body]);
        $response = $client->get($remote_file_url);

        if($response->getStatusCode() === 200)
        {
          $content = $response->getBody();
        }
      }
      catch(\Exception $e)
      {
        $error = $e->getMessage();

        $header = '';

        foreach($headers as $name => $value)
        {
					$header .= $name . ':' . $value . PHP_EOL;     	
        }

        $opts = [
				  'http' => ['header' => $header]
				];

				if($body)
				{
					$opts['http']['content'] = http_build_query($body);
				}

				$context = stream_context_create($opts);

        try
        {
          $resource = fopen($remote_file_url, 'r', false, $context);

          $content = stream_get_contents($resource);

          fclose($resource);
        }
        catch(\Exception $e)
        {
          $error = $e->getMessage();

          try
          {
            $content = file_get_contents($remote_file_url, false, $context);
          }
          catch(\Exception $e)
          {
            $error = $e->getMessage();
          
            try
            {
              $ch = curl_init();

              curl_setopt($ch, CURLOPT_URL, $remote_file_url);
              curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
              curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
              curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
              curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

              if($headers)
              {
              	curl_setopt($ch, CURLOPT_HTTPHEADER, explode(PHP_EOL, $header));
              }

              if($body)
              {
              	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($body));
              }

              $content = curl_exec($ch);
              $error   = curl_error($ch);

              curl_close ($ch);
            }
            catch(\Exception $e)
            {
              $error = $e->getMessage();
            }
          }
        }
      }

      $base_url = explode("?", $remote_file_url, 2)[0] ?? null;

      if(!$extension = pathinfo($base_url, PATHINFO_EXTENSION))
      {
        $finfo = new \finfo(FILEINFO_MIME);

        $extension = config('mimetypes')[explode('; ', $finfo->buffer($content))[0] ?? ''] ?? 'unknown_extension';  
      }

      if($content)
      { 
      	$file_name = $file_name ?? pathinfo($base_url, PATHINFO_FILENAME);
        $file_name = "{$file_name}.{$extension}";
      }
      
      return compact('error', 'content', 'file_name', 'extension');
  }


  function back_with_errors($errors)
  {
  	$errors = new \Illuminate\Support\MessageBag($errors);

    return back()->withErrors($errors)->withInput();
  }




	if(! function_exists('storage_path')) {

	    /**
	     * Get the path to the storage folder.
	     *
	     * @param  string  $path
	     * @return string
	     */
	    function storage_path($path = '')
	    {
	        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, app('path.storage').($path ? DIRECTORY_SEPARATOR.$path : $path));
	    }
	}


	if (! function_exists('public_path')) {
	    /**
	     * Get the path to the public folder.
	     *
	     * @param  string  $path
	     * @return string
	     */
	    function public_path($path = '')
	    {
	        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, app()->make('path.public').($path ? DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR) : $path));
	    }
	}