<?php

	namespace App\Libraries;

  use Illuminate\Support\Facades\{ DB, Session, Cache, Validator, Auth };
  use Illuminate\Http\Request;
  use App\Models\{ Transaction };
  use GuzzleHttp\{ Client };

	class Paymongo 
	{		
		public $name = 'paymongo';
    public $return_url;
    public $cancel_url;
    public $supported_currencies = ["PHP"];
    public $payment_methods = [
			"billease",
			"card",
			"dob",
			"dob_ubp",
			"gcash",
			"grab_pay",
			"paymaya",
    ];
    public $currency_code;
    public $exchange_rate = 1;
    public $decimals;
    public $details  = [];
    public $error_msg;
    public $api_key;
    public $basic_auth;
    public $locale = 'en_US';
    public static $response = "default";


    public function __construct(string $locale = null)
    {
      exists_or_abort(config("payments_gateways.{$this->name}"), __(':payment_proc is not enabled', ['payment_proc' =>  $this->name]));

      $this->currency_code = config('payments.currency_code');
      $this->decimals = config("payments.currencies.{$this->currency_code}.decimals", 2);
      $this->basic_auth = base64_encode(config("payments_gateways.{$this->name}.secret_key").':'.config("payments_gateways.{$this->name}.public_key"));

      if($payment_methods = array_filter(explode(',', config("payments_gateways.{$this->name}.method"))))
      {
      	$this->payment_methods = $payment_methods;
      }

      prepare_currency($this);

      $this->details = [
        'items' => [],
        'total_amount' => 0,
        'currency' => $this->currency_code,
        'exchange_rate' => $this->exchange_rate,
        'custom_amount' => null,
        'reference' => generate_transaction_ref(),
        'transaction_id' => null,
        'order_id' => null,
      ];

      $this->return_url  = route('home.checkout.order_completed', ['processor' => $this->name, 'order_id' => $this->details['reference']]);
      $this->cancel_url  = config('checkout_cancel_url');
    }



		public function create_payment_link(array $params)
		{
      // Doc : https://developers.paymongo.com/reference/create-a-checkout

			extract($params);

      $total_amount = 0;

      foreach($cart as $item)
      {
        $total_amount += $item->price;

        $this->details['items'][] = [
          'name' => $item->name, 
          "id" => $item->id ?? null,
          'value' => format_amount($item->price * $this->exchange_rate, false, $this->decimals),
          'license' => $item->license_id ?? null
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

	      	$breakdown["tax_total"] = ["currency_code" => $this->currency_code,"value" => $value];

		      $this->details["items"]["tax"] = ["name" => __("Tax"), "value" => $value];

		      $total_amount += format_amount($tax ?? 0, false, $this->decimals);
	      }

	      if(config("payments_gateways.{$this->name}.fee", 0))
	      {
	      	$handling = config("payments_gateways.{$this->name}.fee", 0);
	      	$value = format_amount($handling * $this->exchange_rate, false, $this->decimals);

	      	$breakdown["handling"] = ["currency_code" => $this->currency_code,"value" => $value];

		      $this->details["items"]["fee"] = ["name" => __("Handling fee"), "value" => $value];

		      $total_amount += format_amount($value, false, $this->decimals);
	      }
      }

      $this->details["total_amount"] = format_amount(array_sum(array_column($this->details["items"], "value")), false, $this->decimals);

      $total_amount = (int)ceil(format_amount($total_amount, false, $this->decimals) * pow(10, $this->decimals));
		
			$payload = [
				"data" => [
					"attributes" => [
						"send_email_receipt" => false,
						"show_description" => false,
						"show_line_items" => true,
						"cancel_url" => $this->cancel_url,
						"success_url" => $this->return_url,
						"line_items" => [
							[
								"amount" => $total_amount,
								"currency" => "PHP",
								"name" => __("Purchase from :app_name", ["app_name" => mb_ucfirst(config("app.name"))]),
								"quantity" => 1
							]
						],
						"reference_number" => $this->details['reference'],
						"payment_method_types" => $this->payment_methods,
					]
				]
			];

      $client = new Client([
      	"verify" => false,
      	"http_errors" => false,
      	"headers" => [
      		"Content-Type" => "application/json",
      		"Accept" => "application/json",
      		"Authorization" => "Basic {$this->basic_auth}",
      	]
      ]);

      $response = $client->request("POST", "https://api.paymongo.com/v1/checkout_sessions", ["json" => $payload]);

      if(stripos($response->getStatusCode(), '20') === false)
      {
      		$this->error_msg = ['user_message' => (string)$response->getBody()];

        	return;
      }

      $response = json_decode((string)$response->getBody(), true);

      if(isset($response['errors']))
      {
      		$this->error_msg = ['user_message' => __("Failed to complete your request")];

      		\Log::error(json_encode($response['errors']));

        	return;
      }

      return $response;
		}



    public function init_payment(array $config)
    {
      extract($config);

      $response = $this->create_payment_link($params);

      if($this->error_msg)
      {
        return $this->error_msg;
      }

      $url = $response['data']['attributes']['checkout_url'] ?? abort(404);

      $this->details['transaction_id'] = $response['data']['id'] ?? null;

      $params['transaction_details'] = $this->details;
      $params['reference_id']        = $response['data']['attributes']['reference_number'];
      $params['payment_id']          = $response['data']['id'] ?? null;

      if($return_url && $user)
      {
        Cache::put("payment_{$user->id}", $params['reference_id'], now()->addDays(1)); 
      }
      else
      {
        Session::put('payment', $params['reference_id']);
      }

      Cache::put($params['reference_id'], $params, now()->addDays(1));

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

        $response = $this->verify_payment($transaction);

        if($this->error_msg)
        {
        	return;
        }

        $transaction->status = ($response['status'] ?? null) === true ? 'paid' : 'pending';

        $transaction->save();

        return ['status' => true, 'user_message' => null, 'transaction' => $transaction];
      }

      return ['status' => false, 'user_message' => __('Something wrong happened.')];
    }

    
    public function verify_payment($transaction)
    {
      // Doc : https://developers.paymongo.com/reference/retrieve-a-checkout
      
      $client = new Client([
      	"verify" => false,
      	"http_errors" => false,
      	"headers" => [
      		"Accept" => "application/json",
      		"Authorization" => "Basic {$this->basic_auth}",
      	]
      ]);

      $response = $client->request("GET", "https://api.paymongo.com/v1/checkout_sessions/{$transaction->transaction_id}");

      if(stripos($response->getStatusCode(), '20') === false)
      {
      		$this->error_msg = ['user_message' => (string)$response->getBody()];

        	return ['status' => false];
      }

      $response = json_decode((string)$response->getBody(), true);

      $status = $response['data']['attributes']['payment_intent']['attributes']['status'] ?? '';

      if(strtolower($status) === "succeeded")
      {
      	return ['status' => true, 'response' => $response];
      }

     	return ['status' => false];
    }


    public static function init($locale = null)
    {
    	return new Paymongo($locale);
    }
  }