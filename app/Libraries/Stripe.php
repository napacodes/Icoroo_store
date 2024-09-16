<?php

	namespace App\Libraries;

	use Illuminate\Support\Facades\{ DB, Session, Cache, Validator, Auth };
	use Illuminate\Http\Request;
	use App\Models\{ Transaction };

	class Stripe 
	{
		public $name = "stripe";
		public $success_url;
		public $cancel_url;
		public $webhook_url;
		public $payment_method_types = [
			      	"card" => [],
			      	"bancontact" => ["EUR"],
			      	"alipay" => ["AUD", "CAD", "EUR", "GBP", "HKD", "JPY", "NZD", "SGD", "USD", "MYR"],
			      	"eps" => ["EUR"],
			      	"fpx" => ["MYR"],
			      	"giropay" => ["EUR"],
			      	"ideal" => ["EUR"],
			      	"p24" => ["EUR", "PLN"]
			      ];
		public $currency_code;
		public $exchange_rate = 1;
		public $decimals;
		public $details  = [];
		public $error_msg = [];
		public $webhooks_to_delete = [];
		public static $response = "json";


		public function __construct()
		{
			exists_or_abort(config("payments_gateways.{$this->name}.enabled"), __(":payment_proc is not enabled", ["payment_proc" =>  "Stripe"]));

			$this->currency_code = config("payments.currency_code");
			$this->decimals = config("payments.currencies.{$this->currency_code}.decimals");

			prepare_currency($this);

      if($method_types = array_filter(explode(",", config("payments_gateways.{$this->name}.methods", ""))))
      {
      	$this->payment_method_types = array_intersect_key($this->payment_method_types, array_flip($method_types));
      }

      $this->payment_method_types = array_filter($this->payment_method_types, function($v, $k)
															      {
															      	return !count($v) || in_array($this->currency_code, $v); 
															      }, ARRAY_FILTER_USE_BOTH);
      $this->details = [
      	"items" => [],
	      "total_amount" => 0,
	      "currency" => $this->currency_code,
	      "exchange_rate" => $this->exchange_rate,
	      "custom_amount" => null,
	      "reference" => generate_transaction_ref(),
	      'transaction_id' => null,
        'order_id' => null,
	    ];

	    $this->webhook_url = route('home.checkout.webhook', ['processor' => $this->name]);
			$this->success_url = route('home.checkout.order_completed', [
														"stripe_sess_id" => "CHECKOUT_SESSION_ID", 
														'processor' => $this->name, 
														'order_id' => $this->details['reference'
													]]);
      $this->cancel_url = config('checkout_cancel_url');


      if(!cache("stripe_webhook"))
      {
      	if(!$webhook = $this->delete_duplicate_webhooks(true))
      	{
      		$webhook = $this->create_webhook();

      		$webhook_id     = $webhook->id;
      		$webhook_secret = $webhook->secret;
      	}

      	\Cache::forever("stripe_webhook", ["id" => $webhook_id, "secret" => $webhook_secret]);
      }
		}


		// Create checkout session
		public function create_checkout_session(array $params, $user = null)
		{
			

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

      $total_amount = (int)ceil(format_amount($total_amount * $this->exchange_rate, false, $this->decimals) * pow(10, $this->decimals));

			$line_items = [];

      $line_items[] = [
      	'quantity' => 1,
      	'price_data' => [
      		'product_data' => [
      			'name' => __('Purchase from :app_name', ['app_name' => mb_ucfirst(config('app.name'))])
      		],
          'unit_amount_decimal' => $total_amount,
          'currency' => $this->currency_code,
        ]
      ];


      if(!$prepaid_credits_pack_id)
			{
	      if(config("payments.vat", 0))
	      {
	      	$value = (int)ceil(($total_amount * config("payments.vat", 0)) / 100);

	      	$line_items[] = [
	      		'quantity' => 1,
		      	'price_data' => [
		      		'product_data' => [
		      			'name' => __('Tax'),
		      			'description' => config('payments.vat').'%',
		      		],
		          'unit_amount_decimal' => $value,
		          'currency' => $this->currency_code,
		        ]
		      ];

		      $this->details["items"]["tax"] = ["name" => __("Tax"), "value" => format_amount($value / pow(10, $this->decimals), false, $this->decimals)];

		      $total_amount += $value ?? 0;
	      }


	      if(config("payments_gateways.{$this->name}.fee", 0))
	      {
	      	$value = (int)ceil(format_amount(config("payments_gateways.{$this->name}.fee", 0) * $this->exchange_rate, false, $this->decimals)* pow(10, $this->decimals));

	      	$line_items[] = [
		      	'quantity' => 1,
		      	'price_data' => [
		      		'product_data' => [
		      			'name' => __('Fee'),
		      			'description' => __('Handling fee'),
		      		],
		          'unit_amount_decimal' => $value,
		          'currency' => $this->currency_code,
		        ]
		      ];

		      $this->details["items"]["fee"] = ["name" => __("Handling fee"), "value" => format_amount($value / pow(10, $this->decimals), false, $this->decimals)];

		      $total_amount += $value;
	      }
	    }

      $this->details["total_amount"] = format_amount(array_sum(array_column($this->details["items"], "value")), false, $this->decimals);


		  $payload = [
		  	"payment_method_types" => array_keys($this->payment_method_types),
		  	"success_url" => $this->success_url,
		  	"cancel_url" => $this->cancel_url,
		  	"mode" => "payment",
		  	"payment_intent_data" => [
		  		"capture_method" => "automatic"
		  	],
		  	"submit_type" => "pay",
		  	"line_items" => $line_items
		  ];

		  $headers = [ 
				"Content-Type: application/x-www-form-urlencoded",
				"Authorization: Bearer " . config("payments_gateways.{$this->name}.secret_id"),
			];

			$ch = curl_init();

			$post_query = str_replace("CHECKOUT_SESSION_ID", "{CHECKOUT_SESSION_ID}", http_build_query($payload));

			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_query);
			curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/checkout/sessions");
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			$result = curl_exec($ch);

			curl_close($ch);

			$result = json_decode($result);

			if(property_exists($result, "error"))
      {
        $this->error_msg = ["user_message" => json_encode($result->error)];

        return;
      }

      return $result;
		}




		// Retrieve checkout session
		public function get_checkout_session(string $cs = null)
		{
		

			$cs OR die();

			$ch = curl_init();

			 $headers = [
				"Content-Type: application/x-www-form-urlencoded",
				"Authorization: Bearer " . config("payments_gateways.{$this->name}.secret_id"),
			];

			$expand = http_build_query(["expand" => ["payment_intent"]]);

			curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/checkout/sessions/{$cs}?{$expand}");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			$result = curl_exec($ch);
			
			curl_close($ch);

			return $result;
		}



		// Retrieve paymeny intents
		public function get_payment_intents(string $pi_id = "")
		{
			
			$pi_id OR die();

			$ch = curl_init();

			 $headers = [
				"Content-Type: application/x-www-form-urlencoded",
				"Authorization: Bearer " . config("payments_gateways.{$this->name}.secret_id"),
			];

			curl_setopt($ch, CURLOPT_HTTPGET, 1);
			curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/payment_intents/{$pi_id}");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			$result = curl_exec($ch);

			curl_close($ch);

			return $result;
		}



		// Retrieve customer
		public function get_customer($cus)
		{
			

			$cus OR die();

			$ch = curl_init();

			 $headers = [
				"Content-Type: application/x-www-form-urlencoded",
				"Authorization: Bearer " . config("payments_gateways.{$this->name}.secret_id"),
			];

			curl_setopt($ch, CURLOPT_HTTPGET, 1);
			curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/customers/{$cus}");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			$result = curl_exec($ch);

			curl_close($ch);

			return $result;
		}




		// Create a charge 
		public function create_charge($stripeToken)
		{
			

			$coupon 	= json_decode($this->create_coupon(null, 9, "once"));
			$customer = json_decode($this->create_customer($stripeToken, null, $coupon->id));

			$headers = [
				"Content-Type: application/x-www-form-urlencoded",
				"Authorization: Bearer " . config("payments_gateways.{$this->name}.secret_id")
			];

			$payload = [
				"amount" => 42.99*100,
				"currency" => "USD",
				"description" => "Charge for mr X",
				"customer" => $customer->id
			];


			$ch = curl_init();

			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
			curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/charges");
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			$result = curl_exec($ch);

			curl_close($ch);
			
			return $result;
		}
	


		// Create a customer
		public function create_customer($source = null, $description = "", $coupon = null, $tax_id_data = [])
		{
			

			$payload = [];

			if($description)
				$payload["description"] = $description;

			if($source)
				$payload["source"] = $source;

			if($coupon)
				$payload["coupon"] = $coupon;

			if($tax_id_data)
				$payload["tax_id_data"] = $tax_id_data;

			$headers = [
				"Content-Type: application/x-www-form-urlencoded",
				"Authorization: Bearer " . config("payments_gateways.{$this->name}.secret_id")
			];

			$ch = curl_init();

			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/customers");

			if($payload)
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
			
			$result = curl_exec($ch);

			curl_close($ch);
			
			return $result;
		}




		// Create a Tax
		public function create_tax($display_name, $description = "", $percentage = 0, $jurisdiction = "", $inclusive = false)
		{
			

			$payload = [
				"display_name" => $display_name,
				"description" => $description,
				"percentage" => $percentage,
				"jurisdiction" => $jurisdiction,
				"inclusive" => $inclusive
			];

			$headers = [
				"Content-Type: application/x-www-form-urlencoded",
				"Authorization: Bearer " . config("payments_gateways.{$this->name}.secret_id")
			];

			$ch = curl_init();

			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/customers");
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
			
			$result = curl_exec($ch);

			curl_close($ch);
			
			return $result;
		}




		// Create a coupon
		public function create_coupon($amount_off = null, $percent_off = null, $duration = "once")
		{
			

			$payload = ["duration" => $duration];

			if($amount_off)
				$payload["amount_off"] = $amount_off;
			elseif($percent_off)
				$payload["percent_off"] = $percent_off;

			$headers = [
				"Content-Type: application/x-www-form-urlencoded",
				"Authorization: Bearer " . config("payments_gateways.{$this->name}.secret_id")
			];

			$ch = curl_init();

			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/coupons");
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
			
			$result = curl_exec($ch);

			curl_close($ch);
			
			return $result;
		}




		// Create a card token
		public function create_card_token(array $card)
		{
			

			$payload = [
				"number" => $card["number"] ?? null,
				"exp_month" => $card["exp_month"] ?? null,
				"exp_year" => $card["exp_year"] ?? null,
				"cvc" => $card["cvc"] ?? null
			];

			$headers = [
				"Content-Type: application/x-www-form-urlencoded",
				"Authorization: Bearer " . config("payments_gateways.{$this->name}.secret_id")
			];

			$ch = curl_init();

			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/tokens");
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
			
			$result = curl_exec($ch);

			curl_close($ch);
			
			return json_decode($result)->id ?? null;
		}




		// Create payment intents
		public function create_payment_intents($stripeToken)
		{
			

			$coupon 	= json_decode($this->create_coupon(null, 5, "once"));
			$customer = json_decode($this->create_customer($stripeToken, null, $coupon->id));

			$payload = [
				"amount" => 33.58*100,
				"currency" => "usd",
				"confirm" => "true",
				"return_url" => null,
				"customer" => $customer->id
			];

			$headers = [
				"Content-Type: application/x-www-form-urlencoded",
				"Authorization: Bearer " . config("payments_gateways.{$this->name}.secret_id")
			];

			$ch = curl_init();

			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/payment_intents");
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
			
			$result = curl_exec($ch);

			curl_close($ch);

			$this->delete_customer($customer->id);
			
			return $result;
		}





		// Delete a customer
		public function delete_customer($customer_id)
		{
			

			$headers = [
				"Authorization: Bearer " . config("payments_gateways.{$this->name}.secret_id")
			];

			$ch = curl_init();

			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/{$customer_id}");
			
			$result = curl_exec($ch);

			curl_close($ch);
			
			return $result;
		}




		public function get_balance_transaction(string $txn)
		{
			
  		
  		$headers = [
				"Authorization: Bearer " . config("payments_gateways.{$this->name}.secret_id")
			];

			$ch = curl_init();

			curl_setopt($ch, CURLOPT_HTTPGET, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/balance_transactions/{$txn}");
			
			$result = curl_exec($ch);

			curl_close($ch);
			
			return $result;
		}



		public function refund_transaction(string $charge, float $amount = 0)
		{
			
  		
  		$headers = [
  			"Content-Type: application/x-www-form-urlencoded",
				"Authorization: Bearer " . config("payments_gateways.{$this->name}.secret_id")
			];

			$payload = ["charge" => $charge];

			if($amount)
				$payload["amount"] = ceil($amount*100);


			$ch = curl_init();

			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/refunds");
			
			$result = curl_exec($ch);

			curl_close($ch);
			
			return $result;
		}




		public function create_webhook()
		{
				
  		$headers = [
  			"Content-Type: application/x-www-form-urlencoded",
				"Authorization: Bearer " . config("payments_gateways.{$this->name}.secret_id")
			];

			$payload = [
				"url" => $this->webhook_url,
				"enabled_events" => ["charge.succeeded", "charge.failed"]
			];


			$ch = curl_init();

			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/webhook_endpoints");
			
			$result = curl_exec($ch);

			curl_close($ch);
			
			if(!json_decode($result))
			{
				$this->error_msg = ["user_message" => $result];
				
				return;
			}

			return json_decode($result);
		}




		public function get_webhook(string $webhook_id)
		{
			

			$headers = [
  			"Content-Type: application/x-www-form-urlencoded",
				"Authorization: Bearer " . config("payments_gateways.{$this->name}.secret_id")
			];

			$ch = curl_init();

			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/webhook_endpoints/{$webhook_id}");
			
			$result = curl_exec($ch);

			curl_close($ch);

			if(!json_decode($result))
			{
				$this->error_msg = ["user_message" => $result];
				
				return;
			}

			return json_decode($result);
		}



		public function delete_webhook($webhook)
		{
			

			$headers = [
  			"Content-Type: application/x-www-form-urlencoded",
				"Authorization: Bearer " . config("payments_gateways.{$this->name}.secret_id")
			];

			$ch = curl_init();

			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/webhook_endpoints/{$webhook->id}");
			
			$result = curl_exec($ch);

			curl_close($ch);

			if(!json_decode($result))
			{
				$this->error_msg = ["user_message" => $result];
				
				return;
			}

			return json_decode($result)->deleted ?? null;

		}



		public function list_webhooks($limit = 100, $starting_after = null)
		{
			

			$headers = [
  			"Content-Type: application/x-www-form-urlencoded",
				"Authorization: Bearer " . config("payments_gateways.{$this->name}.secret_id")
			];

			$ch = curl_init();

			$query_params = ["limit" => $limit, "starting_after" => $starting_after];
			$query_params = array_filter($query_params);
			$query_params = http_build_query($query_params);
			
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/webhook_endpoints?{$query_params}");
			
			$result = curl_exec($ch);

			curl_close($ch);

			if(!json_decode($result))
			{
				$this->error_msg = ["user_message" => $result];
				
				return;
			}

			return json_decode($result);
		}



		public function delete_duplicate_webhooks($all = false, $limit = 100, $starting_after = null)
		{
      $webhooks = $this->list_webhooks($limit, $starting_after);

      foreach($webhooks->data as $webhook)
      {
        if($webhook->url === route("home.checkout.webhook"))
        {
          $this->webhooks_to_delete[] = $webhook;
        }
      }

      if($webhooks->has_more ?? null)
      {
        $this->delete_duplicate_webhooks($all, $limit, array_pop($webhooks->data)->id);
      }
      else
      {
      	foreach(array_slice($this->webhooks_to_delete, $all ? 0 : 1) as $webhook)
      	{
      		$this->delete_webhook($webhook);
      	}
      
      	return $all ? null : ($this->webhooks_to_delete[0] ?? null);
      }
		}



		public function init_payment(array $config)
		{
			extract($config);

      $response = $this->create_checkout_session($params);

      if($this->error_msg)
      {
        return ['user_message' => $this->error_msg];
      }

      $this->details['transaction_id'] = $response->id;
      $this->details['order_id'] = $response->payment_intent;

      $params['transaction_details'] = $this->details;
      $params['transaction_id'] = $response->id;
      $params['order_id'] = $response->payment_intent;

      Session::put('payment', $response->id);

      Cache::put($response->id, $params, now()->addDays(1));

      return ['id' => $response->id];
		}



		public function complete_payment(Request $request)
    {
      if(stripos($request->processor, $this->name) !== false && $request->order_id !== null)
      {
        $transaction_id = $request->order_id;

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

      if(stripos($request->processor, $this->name) !== false)
      {
      	$status['valid'] = 1;

        parse_str(str_ireplace(',', '&', $request->header('stripe-signature')), $signature);
        
        $timestamp  = $signature['t'];
        $sig0       = $signature['v0'] ?? null;
        $sig1       = $signature['v1'] ?? null;

        $signed_payload = $timestamp.'.'.file_get_contents("php://input");

        $expected_sig =  hash_hmac('sha256', $signed_payload, cache('stripe_webhook.secret'));

       	$valid_sig = (isset($sig0) && hash_equals($expected_sig, $sig0)) || (isset($sig1) && hash_equals($expected_sig, $sig1));

       	$success = $request->input('data.object.captured') === true && $request->input('data.object.paid') === true 
        					 && mb_strtolower($request->input('data.object.status')) === 'succeeded';

        if($valid_sig && $success)
        {
          $order_id = $request->input('data.object.payment_intent');

      		$transaction = 	Transaction::where(function($query) use($order_id)
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

	