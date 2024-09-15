<?php

	namespace App\Libraries;

	use Illuminate\Support\Facades\{ DB, Session, Cache, Validator, Auth };
  use Illuminate\Http\Request;
  use App\Models\{ Transaction };


	class Omise 
	{
		public $name = "omise";
		public $return_url;
		public $supported_currencies = ["JPY", "SGD", "THB"];
		public $currency_code;
		public $exchange_rate = 1;
		public $default_currency;
		public $decimals;
		public $details  = [];
		public $error_msg;
		public static $response = "json";



		public function __construct()
		{
      exists_or_abort(config("payments_gateways.{$this->name}"), __(":payment_proc is not enabled", ["payment_proc" =>  "Omise"]));

      $this->currency_code = config("payments.currency_code");
      $this->decimals = config("payments.currencies.{$this->currency_code}.decimals", 2);
      $this->default_currency = default_currency();

      prepare_currency($this);

      $this->details = [
        "items" => [],
        "total_amount" => 0,
        "currency" => $this->currency_code,
        "exchange_rate" => $this->exchange_rate,
        "custom_amount" => null,
        "reference" => generate_transaction_ref(),
        "transaction_id" => null,
        "order_id" => null,
      ];

			$this->return_url  = route('home.checkout.order_completed', ['processor' => $this->name, 'order_id' => $this->details['reference']]);
		}



		public function create_payment_link(array $params)
		{
			/*
				Doc : https://www.omise.co/links-api
				---------------------------------------- 
				curl https://api.omise.co/links \
				  -u $OMISE_SECRET_KEY: \
				  -d "amount=10000" \
				  -d "currency=thb" \
				  -d "title=Hot Latte" \
				  -d "description=A warm cup of coffee"
			*/

			extract($params);

      $total_amount = 0;

      foreach($cart as $item)
      {
        $total_amount += $item->price;

        $this->details["items"][] = [
          "name"    => $item->name, 
          "id" => $item->id ?? null,
          "value"   => format_amount($item->price * $this->exchange_rate, false, $this->decimals),
          "license" => $item->license_id ?? null
        ];
      }


      if(!$prepaid_credits_pack_id)
      {
	      if(config("pay_what_you_want.enabled") && config("pay_what_you_want.for.".($subscription_id ? "subscriptions": "products")) && $custom_amount)
	      {
	        $total_amount = $custom_amount;

	        $this->details["custom_amount"] = format_amount($custom_amount * $this->exchange_rate, false, $this->decimals);
	      }

	      if(($coupon->status ?? null) && !$custom_amount)
	      {
	        $total_amount -= $coupon->coupon->discount ?? 0;

	        $this->details["items"]["discount"] = ["name" => __("Discount"), "value" => -format_amount($coupon->coupon->discount * $this->exchange_rate, false, $this->decimals)];
	      }
      }

      $total_amount = (int)round(format_amount($total_amount * $this->exchange_rate, false, $this->decimals) * pow(10, $this->decimals));


      if(!$prepaid_credits_pack_id)
      {
	      if(config("payments.vat", 0))
	      {
	        $value = (int)round(($total_amount * config("payments.vat", 0)) / 100);

	        $this->details["items"]["tax"] = ["name" => __("Tax"), "value" => format_amount($value / pow(10, $this->decimals), false, $this->decimals)];

	        $total_amount += $value ?? 0;
	      }

	      if(config("payments_gateways.{$this->name}.fee", 0))
	      {
	        $value = (int)round(format_amount(config("payments_gateways.{$this->name}.fee", 0) * $this->exchange_rate, false, $this->decimals)* pow(10, $this->decimals));

	        $this->details["items"]["fee"] = ["name" => __("Handling fee"), "value" => format_amount($value / pow(10, $this->decimals), false, $this->decimals)];

	        $total_amount += $value;
	      }
      }

      $this->details["total_amount"] = format_amount(array_sum(array_column($this->details["items"], "value")), false, $this->decimals);

			$payload = [
			  "amount"   		=> $total_amount,
			  "currency" 		=> $this->currency_code,
			  "description" => __("Order :num", ["num" => $this->details['reference']]),
			  "title" 	 		=> __("Purchase from :app_name", ["app_name" => mb_ucfirst(config("app.name"))])
			];

			$headers = [];

			$ch = curl_init();

			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch, CURLOPT_USERPWD, config("payments_gateways.{$this->name}.secret_key").":");
			curl_setopt($ch, CURLOPT_URL, "https://api.omise.co/links"); 
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HEADERFUNCTION,
			  function($curl, $header) use (&$headers)
			  {
			    $len = strlen($header);
			    $header = explode(":", $header, 2);
			    if (count($header) < 2) // ignore invalid headers
			      return $len;

			    $headers[strtolower(trim($header[0]))][] = trim($header[1]);

			    return $len;
			  }
			);

			$result = json_decode(curl_exec($ch) ?? []);

			$curl_errno = curl_errno($ch);
			$error_msg = curl_error($ch);

			curl_close($ch);

			if($curl_errno)
			{
				$this->error_msg = ["user_message" => $error_msg];

				return;
			}

			if(property_exists($result, "error"))
			{
				$this->error_msg = ["user_message" => json_encode($resul)];

				return;
			}

			return $result;
		}



		public function create_order(array $params, $prepare = true, $source = null)
		{
			/*
				Doc : https://www.omise.co/charges-api
				---------------------------------------- 
				curl https://api.omise.co/charges \
			  -u $OMISE_SECRET_KEY: \
			  -d "amount=100000" \
			  -d "currency=thb" \
			  -d "card=tokn_test_5g5mep9yrko3vx2f0hx"
			*/

			extract($params);

      $total_amount = 0;

      foreach($cart as $item)
      {
        $total_amount += $item->price;

        $this->details["items"][] = [
          "name"    => $item->name, 
          "value"   => format_amount($item->price * $this->exchange_rate, false, $this->decimals),
          "license" => $item->license_id ?? null
        ];
      }


      if(!$prepaid_credits_pack_id)
      {
	      if(config("pay_what_you_want.enabled") && config("pay_what_you_want.for.".($subscription_id ? "subscriptions": "products")) && $custom_amount)
	      {
	        $total_amount = $custom_amount;

	        $this->details["custom_amount"] = format_amount($custom_amount * $this->exchange_rate, false, $this->decimals);
	      }

	      if(($coupon->status ?? null) && !$custom_amount)
	      {
	        $total_amount -= $coupon->coupon->discount ?? 0;

	        $this->details["items"]["discount"] = ["name" => __("Discount"), "value" => -format_amount($coupon->coupon->discount * $this->exchange_rate, false, $this->decimals)];
	      }
	    }

      $total_amount = (int)round(format_amount($total_amount * $this->exchange_rate, false, $this->decimals) * pow(10, $this->decimals));

      if(!$prepaid_credits_pack_id)
      {
	      if(config("payments.vat", 0))
	      {
	        $value = (int)round(($total_amount * config("payments.vat", 0)) / 100);

	        $this->details["items"]["tax"] = ["name" => __("Tax"), "value" => format_amount($value / pow(10, $this->decimals), false, $this->decimals)];

	        $total_amount += $value ?? 0;
	      }


	      if(config("payments_gateways.{$this->name}.fee", 0))
	      {
	        $value = (int)round(format_amount(config("payments_gateways.{$this->name}.fee", 0) * $this->exchange_rate, false, $this->decimals)* pow(10, $this->decimals));

	        $this->details["items"]["fee"] = ["name" => __("Handling fee"), "value" => format_amount($value / pow(10, $this->decimals), false, $this->decimals)];

	        $total_amount += $value;
	      }
      }

      $this->details["total_amount"] = format_amount(array_sum(array_column($this->details["items"], "value")), false, $this->decimals);

			$payload = [
			  "amount"   		=> (int)$total_amount,
			  "currency" 		=> $this->currency_code,
			  "description" => __("Order :num", ["num" => $this->details['reference']]),
			  "title" 	 		=> __("Purchase from :app_name", ["app_name" => mb_ucfirst(config("app.name"))]),
			  "return_uri"  => $this->return_url,
			  "capture"     => true
			];

			if($prepare)
			{
				$payload["status"] = true;

				return $payload;	
			}
			else
			{
				$payload = array_merge($payload, $source);
			}

			$ch = curl_init();

			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch, CURLOPT_USERPWD, config("payments_gateways.{$this->name}.secret_key").":");
			curl_setopt($ch, CURLOPT_URL, "https://api.omise.co/charges"); 
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			$result = json_decode(curl_exec($ch) ?? []);

			$curl_errno = curl_errno($ch);
			$error_msg = curl_error($ch);

			curl_close($ch);

			if($curl_errno)
			{
				$this->error_msg = ["user_message" => $error_msg];

				return;
			}

			if(property_exists($result, "error"))
			{
				$this->error_msg = ["user_message" => json_encode($resul)];

				return;
			}

			return $result;
		}


		public static function check_order_status($charge_id)
		{
			$ch = curl_init();

			curl_setopt($ch, CURLOPT_HTTPGET, 1);
			curl_setopt($ch, CURLOPT_USERPWD, config("payments_gateways.{$this->name}.secret_key").":");
			curl_setopt($ch, CURLOPT_URL, "https://api.omise.co/charges/{$charge_id}"); 
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			$result = json_decode(curl_exec($ch) ?? []);

			curl_close($ch);

			return $result;
		}


		public function init_payment(array $config)
		{
			extract($config);
				
      if($request->prepare)
      {
        $response = $this->create_order($params, true);

        if($this->error_msg)
        {
          return $this->error_msg;
        }

        return $response; 
      }
      else
      {
        $source = $request->post('omiseSource') 
                  ? ['source' => $request->post('omiseSource')] 
                  : ($request->post('omiseToken') ? ['card' => $request->post('omiseToken')] : null);

        if(!$source)
        {
          return ['user_message' => __('Missing source or token.')];
        }

        $response = $this->create_order($params, false, $source);
        
        if($this->error_msg)
        {
          return $this->error_msg;
        }

        if($authorize_uri = ($response->authorize_uri ?? null))
        {
        	$this->details['transaction_id'] = $response->id;

          $params['transaction_details'] = $this->details;
          $params['transaction_id'] = $response->id;

          Session::put('payment', $response->id);

          Cache::put($response->id, $params, now()->addDays(1));

          return $authorize_uri;
        }
        
        return ['user_message' => __('Missing autorize url.')];
      }
		}


		public function complete_payment(Request $request)
    {
      if(stripos($request->processor, $this->name) !== false && $request->order_id !== null)
      {
        $transaction_id = $request->order_id;

        if(is_null($transaction_id))
        {
          return [
            'status' => false, 
            'user_message' => __('Missing transaction order_id.')
          ];
        }

        $transaction =  Transaction::where(['processor' => $this->name])
                        ->where(function($builder) use($transaction_id)
                        {
                          $builder->where(['transaction_id' => $transaction_id])
                                  ->orWhere(['order_id' => $transaction_id])
                                  ->orWhere(['reference_id' => $transaction_id]);
                        })
                        ->first();

        if(!$transaction)
        {
          return [
            'status' => false, 
            'user_message' => __('Missing transaction database record [:transaction_id].', ['transaction_id' => $transaction_id])
          ];
        }

        if($transaction->status !== 'paid')
        {
          $transaction->status = 'pending';
          $transaction->save();
        }

        return ['status' => true, 'user_message' => null, 'transaction' => $transaction];
      }

      return ['status' => false, 'user_message' => __('Something wrong happened.')];
    }




    public function handle_webhook_notif(Request $request)
    {
      $response = ['status' => 0, 'transaction' => null, 'valid' => 0];

      if(stripos($request->processor, $this->name) !== false && $request->input('data.id') !== null)
      {        
        $status['valid'] = 1;

        $paid				= $request->input('data.paid') === true;
        $authorized	= $request->input('data.authorized') === true;
        $capture		= $request->input('data.capture') === true;

        if($paid && $authorized && $capture)
        {
          $order_id = $request->input('data.id');

          $transaction =  Transaction::where(function($query) use($order_id)
                          {
                            $query->where('order_id', $order_id)
                                  ->orWhere('transaction_id', $order_id)
                                  ->orWhere('reference_id', $order_id);
                          })
                          ->where(['processor' => $this->name, 'status' => 'pending'])
                          ->first();

          if($transaction)
          {
            $transaction->status = 'paid';
            $transaction->confirmed = 1;

            $transaction->save();

            $response['status'] = 1;
            $response['transaction'] = $transaction;
          }
        }
      }

      return $response;
    }

	}