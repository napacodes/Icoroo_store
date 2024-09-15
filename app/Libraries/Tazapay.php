<?php

	namespace App\Libraries;

  use Illuminate\Support\Facades\{ DB, Session, Cache, Validator, Auth };
  use Illuminate\Http\Request;
  use App\Models\{ Transaction };
  use GuzzleHttp\{ Client };

	class Tazapay 
	{		
		public $name = 'tazapay';
    public $return_url;
    public $cancel_url;
    public $supported_currencies = ["USD"];
    public $payment_methods = ["card"];
    public $currency_code;
    public $exchange_rate = 1;
    public $decimals;
    public $is_sandbox;
    public $details  = [];
    public $error_msg;
    public $basic_auth;
    public $locale = 'en_US';
    public static $response = "default";


    public function __construct(string $locale = null)
    {
      exists_or_abort(config("payments_gateways.{$this->name}"), __(':payment_proc is not enabled', ['payment_proc' =>  $this->name]));

      $this->basic_auth = [config("payments_gateways.{$this->name}.api_key"), config("payments_gateways.{$this->name}.secret_key")];
      $this->currency_code = config('payments.currency_code');
      $this->decimals = config("payments.currencies.{$this->currency_code}.decimals", 2);

      if($payment_methods = array_filter(explode(',', config("payments_gateways.{$this->name}.method"))))
      {
      	$this->payment_methods = $payment_methods;
      }

      prepare_currency($this);

      if($locale && in_array($locale, $this->supported_locales))
      {
        $this->locale = $locale;
      }

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
      $this->webhook_url = route('home.checkout.webhook', ['processor' => $this->name, 'order_id' => $this->details['reference']]);
    }



		public function create_order(array $params)
		{
      // Doc : https://docs.tazapay.com/reference/create-checkout

			extract($params);      

      if(\Auth::check() && (!isset($user['firstname']) || !isset($user['lastname'])))
      {
        $this->error_msg = ['user_message' => __("firstname or lastname is missing")];

        return;
      }

      $buyer_name = $user ? "{$user->lastname} {$user->firstname}" : "{$buyer['lastname']} {$buyer['firstname']}";

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

      $total_amount = format_amount($total_amount, false, $this->decimals);

      $payload = [
        "invoice_currency" => $this->currency_code,
        "amount" => (int)($total_amount * pow(10, $this->decimals)),
        "customer_details" => [
          "name" => $buyer_name,
          "email" => $user->email ?? $buyer['email'],
          "country" => user_country('197.144.187.221', 'isoCode')
        ],
        "success_url" => $this->return_url,
        "cancel_url" => $this->cancel_url,
        "webhook_url" => $this->webhook_url,
        "payment_methods" => ['card'],
        "transaction_description" => __("Purchase from :app_name", ["app_name" => mb_ucfirst(config("app.name"))]),
        "reference_id" => $this->details['reference'],
      ];

      $client = new Client([
        "auth" => $this->basic_auth,
      	"verify" => false,
      	"http_errors" => false,
      	"headers" => [
          "Accept" => "application/json",
          "Content-Type" => "application/json",
      	]
      ]);

      $endpoint = config("payments_gateways.{$this->name}.mode") === "sandbox" ? "https://service-sandbox.tazapay.com/v3/checkout" : "https://service.tazapay.com/v3/checkout";

      $response = $client->request("POST", $endpoint, ["json" => $payload]);

      if(stripos($response->getStatusCode(), '20') === false)
      {
      		$this->error_msg = ['user_message' => (string)$response->getBody()];

        	return;
      }

      $response = json_decode((string)$response->getBody(), true);
      
      $status = $response['status'] ?? null;

      if($status === "error")
      {
          $this->error_msg = ['user_message' => __("Failed to complete your request, please contact support")];

          \Log::error(json_encode($response));

          return;
      }

      return $response;
		}



    public function init_payment(array $config)
    {
      extract($config);

      $response = $this->create_order($params);

      if($this->error_msg)
      {
        return $this->error_msg;
      }

      $response = $response['data'];

      $url = $response['url'] ?? abort(404);

      $params['transaction_details'] = $this->details;
      $params['reference_id']        = $response['reference_id'];
      $params['payment_id']          = $response['id'];

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

        if($transaction->status !== 'paid')
        {
          $transaction->status = 'pending';

          $transaction->save();
        }

        return ['status' => true, 'user_message' => null, 'transaction' => $transaction];
      }

      return ['status' => false, 'user_message' => __('Something wrong happened.')];
    }

    
    public function verify_payment($transaction)
    {      
      // Doc : https://docs.tazapay.com/reference/get-checkout-session
      
      $api_url =  config("payments_gateways.{$this->name}.mode") === "sandbox" 
                  ? "https://service-sandbox.tazapay.com/v3/checkout/{$transaction->transaction_id}" 
                  : "https://service.tazapay.com/v3/checkout/{$transaction->transaction_id}";

      $client = new Client([
        "auth" => $this->basic_auth,
        "verify" => false,
        "http_errors" => false,
        "headers" => [
          "Accept" => "application/json",
          "Content-Type" => "application/json",
        ]
      ]);

      $response = $client->request("GET", $api_url);

      if(stripos($response->getStatusCode(), '20') === false)
      {
      		$this->error_msg = ['user_message' => (string)$response->getBody()];

        	return;
      }

      $response = json_decode((string)$response->getBody(), true);

      $status = strtolower($response['status'] ?? '');

      if($status === "success" && ($response['payment_status'] ?? null) === "paid")
      {
      	return ['status' => true, 'response' => $response];
      }

     	return ['status' => false];
    }


    public function handle_webhook_notif(Request $request)
    {
      $response = ['status' => 0, 'transaction' => null, 'valid' => 0];

      if(stripos($request->processor, $this->name) !== false && $request->post('id') && $request->query('order_id'))
      {
        $status['valid'] = 1;
        
        $response = $this->verify_payment($request->post('id'));

        if($this->error_msg)
        { 
	        	return $response;
        }

        $order_id = $request->query('order_id');

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
          $transaction->status = $response['status'] === true ? 'paid' : 'pending';
          $transaction->transaction_id = $request->post('id');

          $transaction->save();

          $response['status'] = 1;
          $response['transaction'] = $transaction;
        }
      }

      return $response;
    }
  }