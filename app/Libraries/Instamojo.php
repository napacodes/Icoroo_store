<?php

	namespace App\Libraries;

  use Illuminate\Support\Facades\{ DB, Session, Cache, Validator, Auth };
  use Illuminate\Http\Request;
  use App\Models\{ Transaction };

	class Instamojo 
	{
    public $name = "instamojo";
    public $supported_currencies = ["INR"];
    public $return_url;
    public $cancel_url;
    public $currency_code;
    public $exchange_rate = 1;
    public $decimals = 2;
    public $details = [];
    public $error_msg;
    public static $response = "default";


    public function __construct()
    {      
      exists_or_abort(config("payments_gateways.{$this->name}"), __(":payment_proc is not enabled", ["payment_proc" =>  "Instamojo"]));

      $this->currency_code = config("payments.currency_code");
      $this->decimals      = config("payments.currencies.{$this->currency_code}.decimals");

      prepare_currency($this);

      $this->details = [
        "items" => [],
        "total_amount" => 0,
        "currency" => $this->currency_code,
        "exchange_rate" => $this->exchange_rate,
        "custom_amount" => null,
        'reference' => generate_transaction_ref(),
        "transaction_id" => null,
        "order_id" => null,
      ];

      $this->return_url = route("home.checkout.order_completed", ['processor' => $this->name, 'order_id' => $this->details['reference']]);
      $this->cancel_url = config("checkout_cancel_url");
    }



		public function create_request(array $params, $user = null)
		{
			/* DOC : https://docs.instamojo.com/reference/create-a-payment-request-1
			---------------------------------------------------------------------------------------
        curl --request POST \
        --url https://api.instamojo.com/v2/payment_requests/ \
        --header 'Authorization: Bearer {{access_token}}' \
        --header 'accept: application/json' \
        --header 'content-type: application/x-www-form-urlencoded' \
        --data allow_repeated_payments=false \
        --data send_email=false \
        --data send_sms=false \
        --data amount=2500 \
        --data purpose=Purchase \
        --data buyer_name=John \
        --data email=john@gmail.com \
        --data phone=212629902623 \
        --data redirect_url=https://tendra.co/checkout/payment/completed \
        --data webhook=https://tendra.co/checkout/webhook
			*/

      $access_token = $this->get_access_token();

      if($this->error_msg)
      {
        return;
      }

			extract($params);

      $total_amount = 0;

      foreach($cart as $item)
      {
        $total_amount += $item->price;

        $this->details["items"][] = [
          "name" => $item->name, 
          "id" => $item->id ?? null,
          "value" => format_amount($item->price * $this->exchange_rate, false, $this->decimals),
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


      $total_amount = format_amount($total_amount * $this->exchange_rate, false, $this->decimals);


      if(!$prepaid_credits_pack_id)
      {
        if(config("payments.vat", 0))
        {
          $tax = ($total_amount * config("payments.vat", 0)) / 100;
          $value = format_amount($tax, false, $this->decimals);

          $this->details["items"]["tax"] = ["name" => __("Tax"), "value" => $value];

          $total_amount += format_amount($tax ?? 0, false, $this->decimals);
        }


        if(config("payments_gateways.{$this->name}.fee", 0))
        {
          $value = format_amount(config("payments_gateways.{$this->name}.fee", 0) * $this->exchange_rate, false, $this->decimals);

          $this->details["items"]["fee"] = ["name" => __("Handling fee"), "value" => $value];

          $total_amount += format_amount($value, false, $this->decimals);
        }
      }


      $this->details["total_amount"] = format_amount(array_sum(array_column($this->details["items"], "value")), false, $this->decimals);


      $total_amount = format_amount($total_amount, false, $this->decimals);


			$api_url = "https://test.instamojo.com/v2/payment_requests/";

      if(config("payments_gateways.{$this->name}.mode") === "live")
      {
        $api_url = "https://api.instamojo.com/v2/payment_requests/";
      }

      $api_key     = config("payments_gateways.{$this->name}.private_api_key");
      $auth_token  = config("payments_gateways.{$this->name}.private_auth_token");
      $webhook_url = "https://c713-197-144-181-96.eu.ngrok.io/checkout/webhook?processor={$this->name}&order_id={$this->details['reference']}&t=".time();
                      //route("home.checkout.webhook", ['processor' => $this->name, 'order_id' => $this->details['reference']]);

			$payload = [
		    "purpose" => __("Purchase from :app_name", ["app_name" => mb_ucfirst(config("app.name"))]),
		    "amount" => $total_amount,
		    "redirect_url" => $this->return_url,
		    "send_email" => false,
		    "send_sms" => false,
		    "allow_repeated_payments" => false,
        "webhook" => $webhook_url
      ];

      if(\Auth::check())
      {
        $user = $user ?? auth()->user();

        if(!$user->phone)
        {
          $this->error_msg = ["user_message" => __("Missing phone number, please enter your phone number in your profile page.")];

          return;
        }
        elseif(!$user->lastname || !$user->firstname)
        {
          $this->error_msg = ["user_message" => __("Buyer firstname or lastname is missing.")];

          return;
        }

        $user_info = [
          "phone"      => $user->phone,
          "buyer_name" => "{$user->lastname} {$user->firstname}",
          "email"      => $user->email
        ];

        $payload = array_merge($payload, array_filter($user_info));
      }

      $headers = [
        "Authorization: Bearer {$access_token}",
        "accept: application/json",
        "content-type: application/x-www-form-urlencoded" 
      ];

      $ch = curl_init();

      curl_setopt($ch, CURLOPT_URL, $api_url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));

			$result = curl_exec($ch);

			if(curl_errno($ch) || !json_decode($result))
			{
				$error_msg = curl_error($ch);

				curl_close($ch);

				$this->error_msg = ["user_message" => $error_msg];

        return;
      }
      
      curl_close($ch);

      $result = json_decode($result);

      if($result->longurl ?? null)
      {
        $this->details['transaction_id'] = $result->id;

        return $result->longurl;
      }

      if(isset($result->success))
      {
        $this->error_msg = ["user_message" =>  serialize($result->message)];
      }
      else
      {
        $errors = [];

        foreach($result as $key => $val)
        {
          $errors[] = "{$key} {$val}";
        }

        $this->error_msg = ["user_message" => implode(',', $errors)];
      }
      
      return;
		}




    public function get_access_token()
    {
      if($access_token = Cache::get('instamojo_access_token'))
      {
        return $access_token;
      }

      $api_url = "https://test.instamojo.com/oauth2/token/";

      if(config("payments_gateways.{$this->name}.mode") === "live")
      {
        $api_url = "https://api.instamojo.com/oauth2/token/";
      }

      $payload = [
        "grant_type"    => "client_credentials",
        "client_id"     => config("payments_gateways.{$this->name}.client_id"),
        "client_secret" => config("payments_gateways.{$this->name}.secret_id")
      ];

      $ch = curl_init();

      curl_setopt($ch, CURLOPT_URL, $api_url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_HTTPGET, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));

      $result = curl_exec($ch);

      if(curl_errno($ch) || !json_decode($result))
      {
        $error_msg = curl_error($ch);

        curl_close($ch);

        $this->error_msg = ["user_message" => $error_msg];

        return;
      }
      
      curl_close($ch);

      $result = json_decode($result);

      if($result->access_token ?? null)
      {
        Cache::put('instamojo_access_token', $result->access_token, now()->addSeconds($result->expires_in));

        return $result->access_token;
      }

      $this->error_msg = ["user_message" => $result->error ?? __('Unable to get an access token.')];

      return;
    }



    public function init_payment(array $config)
    {
      extract($config);

      $url = $this->create_request($params, $user);

      if($this->error_msg)
      {
        return $this->error_msg;
      }

      $params['transaction_details'] = $this->details;
      $params['transaction_id']      = $this->details['transaction_id'];

      if($return_url && $user)
      {
        Cache::put("payment_{$user->id}", $params['transaction_id'], now()->addDays(1)); 
      }
      else
      {
        Session::put('payment', $params['transaction_id']);
      }

      Cache::put($params['transaction_id'], $params, now()->addDays(1));

      return $url;
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

      if(stripos($request->processor, $this->name) !== false && $request->order_id !== null)
      {        
        $status['valid'] = 1;

        $data = $request->post();
        unset($data['mac']);
        $ver = explode('.', phpversion());
        $major = (int)$ver[0];
        $minor = (int)$ver[1];

        ($major >= 5 and $minor >= 4) ? ksort($data, SORT_STRING | SORT_FLAG_CASE) : uksort($data, 'strcasecmp');

        $mac_calculated = hash_hmac("sha1", implode("|", $data), config("payments_gateways.{$this->name}.private_salt"));

        if(($request->post('mac') == $mac_calculated) && strtolower($request->post('status')) === 'credit')
        {
          $order_id = $request->post('order_id');

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



